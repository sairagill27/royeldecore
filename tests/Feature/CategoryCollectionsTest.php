<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryCollectionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name, array $o = []): Category
    {
        return Category::create(array_merge([
            'name'       => $name,
            'slug'       => Str::slug($name),
            'is_active'  => true,
            'sort_order' => 1,
        ], $o));
    }

    public function test_the_section_is_hidden_when_there_are_no_categories(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Shop by category')
            ->assertDontSee('sgh-collections', false);
    }

    public function test_each_card_is_a_bootstrap_card_with_a_visible_shop_now_button(): void
    {
        $this->makeCategory('Wall Clocks');

        $this->get('/')->assertOk()
            ->assertSee('Shop by category')
            ->assertSee('Find the perfect piece for')
            ->assertSee('<em>every space</em>', false)
            ->assertSee('class="card sgh-collection h-100', false)
            ->assertSee('href="' . url('/shop/wall-clocks') . '"', false)
            ->assertSee('Wall Clocks')
            ->assertSee('stretched-link', false)
            ->assertSee('Shop now')
            ->assertSee('Browse the whole shop');
    }

    public function test_the_grid_class_follows_the_number_of_categories(): void
    {
        foreach (range(1, 6) as $n) {
            $this->makeCategory('Category ' . $n, ['sort_order' => $n]);

            $this->get('/')->assertOk()->assertSee('sgh-collections--n' . $n, false);
        }
    }

    public function test_four_categories_use_four_columns_and_five_use_three_per_row(): void
    {
        foreach (range(1, 4) as $n) {
            $this->makeCategory('Category ' . $n, ['sort_order' => $n]);
        }
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(4, substr_count($html, 'col-12 col-sm-6 col-lg-3'));

        $this->makeCategory('Category 5', ['sort_order' => 5]);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(5, substr_count($html, 'col-12 col-sm-6 col-lg-4'));
        $this->assertStringContainsString('justify-content-center', $html);
    }

    public function test_at_most_six_cards_are_shown(): void
    {
        foreach (range(1, 8) as $n) {
            $this->makeCategory('Category ' . $n, ['sort_order' => $n]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(6, substr_count($html, 'card sgh-collection h-100'));
    }

    public function test_the_description_is_shown_short_and_as_plain_text(): void
    {
        $this->makeCategory('Mirrors', ['description' => 'Hand-finished mirrors for every wall']);
        $this->makeCategory('Clocks', ['description' => str_repeat('a', 300)]);
        $this->makeCategory('Vases', ['description' => '<script>alert(1)</script> Soft shapes']);

        $this->get('/')->assertOk()
            ->assertSee('Hand-finished mirrors for every wall')
            ->assertSee(str_repeat('a', 95) . '...')
            ->assertDontSee(str_repeat('a', 96))
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_a_category_without_a_description_gets_a_neutral_line(): void
    {
        $this->makeCategory('Lamps');

        $this->get('/')->assertOk()->assertSee('Explore our Lamps collection.');
    }

    public function test_a_photo_is_used_when_there_is_one_and_an_icon_when_there_is_not(): void
    {
        $this->makeCategory('With Photo', ['image' => 'categories/photo.jpg', 'sort_order' => 1]);
        $this->makeCategory('Without Photo', ['sort_order' => 2]);

        $html = $this->get('/')->assertOk()
            ->assertSee('storage/categories/photo.jpg', false)
            ->assertSee('loading="lazy"', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'storage/categories/photo.jpg'));
        $this->assertStringContainsString('class="bi bi-grid"', $html);   // the icon for the card without a photo
    }

    public function test_hidden_categories_never_show_and_names_are_never_run_as_code(): void
    {
        $this->makeCategory('Visible Lamps');
        $this->makeCategory('Secret Room', ['is_active' => false]);
        $this->makeCategory('<b>Evil</b>', ['slug' => 'evil']);

        $this->get('/')->assertOk()
            ->assertSee('Visible Lamps')
            ->assertDontSee('Secret Room')
            ->assertDontSee('<b>Evil</b>', false)
            ->assertSee('&lt;b&gt;Evil&lt;/b&gt;', false);
    }

    public function test_the_piece_count_counts_only_visible_products(): void
    {
        $category = $this->makeCategory('Mirrors');
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id, 'status' => 'draft']);

        $this->get('/')->assertOk()->assertSee('sgh-collection-count', false)->assertSee('2 pieces');
    }
}
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name, string $slug, int $order = 1): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'is_active' => true, 'sort_order' => $order]);
    }

    private function makeReview($product, $user, array $o = []): ProductReview
    {
        $review = new ProductReview([
            'rating' => $o['rating'] ?? 5,
            'title'  => $o['title'] ?? null,
            'body'   => $o['body'] ?? 'Lovely piece, exactly as described.',
        ]);
        $review->product_id = $product->id;
        $review->user_id    = $user->id;
        $review->status     = $o['status'] ?? 'approved';
        $review->save();

        return $review;
    }

    // ---------- sections that are always there ----------

    public function test_the_calculators_band_is_always_there_and_the_ordering_section_is_gone(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('class="sgh-home-tools"', false)
            ->assertSee('Plan your room before you buy')
            ->assertDontSee('How ordering works')
            ->assertDontSee('Delivery charge:');
    }

    public function test_the_calculators_band_links_to_every_working_tool(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $band = substr($html, strpos($html, 'class="sgh-home-tools"'), 6000);

        foreach (array_keys(config('tools')) as $slug) {
            $this->assertStringContainsString(route('tools.show', $slug), $band);
        }
        $this->assertStringContainsString(route('tools.index'), $band);
    }

    public function test_the_home_page_makes_no_promises_the_shop_has_not_written_down(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Fast Delivery')
            ->assertDontSee('Easy Returns')
            ->assertDontSee('Secure Shopping')
            ->assertDontSee('Premium Quality')
            ->assertDontSee('Free delivery');
    }

    // ---------- categories and new arrivals ----------

    public function test_a_category_card_shows_how_many_visible_pieces_it_has(): void
    {
        $category = $this->makeCategory('Mirrors', 'mirrors');
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id, 'status' => 'draft']);   // not counted

        $this->get('/')->assertOk()
            ->assertSee('sgh-collection', false)
            ->assertSee('Mirrors')
            ->assertSee('2 pieces');
    }

    public function test_one_piece_is_not_called_pieces(): void
    {
        $category = $this->makeCategory('Clocks', 'clocks');
        $this->makeProduct(['category_id' => $category->id]);

        $this->get('/')->assertOk()->assertSee('1 piece')->assertDontSee('1 pieces');
    }

    public function test_new_arrivals_show_products_that_are_not_featured_and_hide_when_there_are_none(): void
    {
        $this->get('/')->assertOk()->assertDontSee('New arrivals');

        $this->makeProduct(['name' => 'Star Lamp', 'is_featured' => true]);
        $this->get('/')->assertOk()->assertDontSee('New arrivals');       // everything is featured

        $this->makeProduct(['name' => 'Plain Vase', 'is_featured' => false]);
        $this->get('/')->assertOk()->assertSee('New arrivals')->assertSee('Plain Vase');
    }

    // ---------- customer reviews ----------

    public function test_the_reviews_section_is_hidden_until_there_is_an_approved_review(): void
    {
        $product = $this->makeProduct();
        $this->makeReview($product, $this->makeCustomer(), ['status' => 'pending', 'body' => 'Still waiting for approval']);

        $this->get('/')->assertOk()
            ->assertDontSee('What our customers say')
            ->assertDontSee('Still waiting for approval');
    }

    public function test_only_approved_reviews_of_visible_products_are_shown_with_a_short_name(): void
    {
        $product = $this->makeProduct(['name' => 'Gold Mirror']);
        $draft   = $this->makeProduct(['name' => 'Hidden Vase', 'status' => 'draft']);

        $this->makeReview($product, $this->makeCustomer(['name' => 'Sara Khan', 'email' => 'sara.secret@example.com']), ['body' => 'Beautiful and well made', 'title' => 'Love it']);
        $this->makeReview($product, $this->makeCustomer(), ['status' => 'rejected', 'body' => 'A rejected opinion']);
        $this->makeReview($draft, $this->makeCustomer(), ['body' => 'Review of a hidden product']);

        $this->get('/')->assertOk()
            ->assertSee('What our customers say')
            ->assertSee('Beautiful and well made')
            ->assertSee('Love it')
            ->assertSee('Sara K.')
            ->assertSee('Verified buyer')
            ->assertSee('Gold Mirror')
            ->assertDontSee('A rejected opinion')
            ->assertDontSee('Review of a hidden product')
            ->assertDontSee('sara.secret@example.com');
    }

    public function test_html_in_a_review_is_shown_as_plain_text_on_the_home_page(): void
    {
        $product = $this->makeProduct();
        $this->makeReview($product, $this->makeCustomer(['name' => '<b>Evil</b> Person']), ['title' => '<i>Hi</i>', 'body' => '<script>alert(1)</script> nice piece']);

        $this->get('/')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<i>Hi</i>', false)
            ->assertDontSee('<b>Evil</b>', false);
    }

    // ---------- the questions band ----------

    public function test_the_questions_band_is_hidden_without_a_valid_contact(): void
    {
        config(['contact.whatsapp' => null, 'contact.email' => null]);
        $this->get('/')->assertOk()->assertDontSee('Questions about a piece?');

        config(['contact.whatsapp' => '123', 'contact.email' => 'not-an-email']);
        $this->get('/')->assertOk()
            ->assertDontSee('Questions about a piece?')
            ->assertDontSee('wa.me', false)
            ->assertDontSee('mailto:', false);
    }

    public function test_the_questions_band_shows_whatsapp_and_email_when_they_are_valid(): void
    {
        config(['contact.whatsapp' => '+92 300-1234567', 'contact.email' => null]);
        $this->get('/')->assertOk()
            ->assertSee('Questions about a piece?')
            ->assertSee('Chat on WhatsApp')
            ->assertSee('https://wa.me/923001234567?text=', false)
            ->assertDontSee('Email us');

        config(['contact.whatsapp' => null, 'contact.email' => 'hello@example.com']);
        $this->get('/')->assertOk()
            ->assertSee('Email us')
            ->assertSee('mailto:hello@example.com', false)
            ->assertDontSee('Chat on WhatsApp');
    }

    // ---------- what was already there ----------

    public function test_the_hero_and_the_journal_are_still_on_the_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Beautiful Spaces Start with the Right')
            ->assertSee('class="sgh-banner"', false)
            ->assertSee('Handpicked for your home');
    }
}
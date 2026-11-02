<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopLookTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name, string $slug, bool $active = true, int $order = 1): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'is_active' => $active, 'sort_order' => $order]);
    }

    public function test_the_shop_header_is_the_green_header_and_keeps_the_category_pills(): void
    {
        $this->makeCategory('Mirrors', 'mirrors');

        $this->get('/shop')->assertOk()
            ->assertSee('class="sgh-tool-head sgh-shop-head"', false)
            ->assertSee('sgh-tool-leaves', false)
            ->assertSee('Shop Décor')
            ->assertSee('class="sgh-pill active"', false)
            ->assertSee('href="' . url('/shop/mirrors') . '"', false);
    }

    public function test_a_category_page_names_the_collection_and_marks_its_pill(): void
    {
        $this->makeCategory('Mirrors', 'mirrors');

        $this->get('/shop/mirrors')->assertOk()
            ->assertSee('Collection')
            ->assertSee('Mirrors')
            ->assertSee('aria-current="page"', false);
    }

    public function test_the_filter_panel_has_every_part_and_a_pinned_apply_button(): void
    {
        config(['shop.currency' => 'Rs.']);

        $this->get('/shop')->assertOk()
            ->assertSee('<details', false)
            ->assertSee('Categories')
            ->assertSee('Price (Rs.)')
            ->assertSee('name="min"', false)
            ->assertSee('name="max"', false)
            ->assertSee('name="in_stock"', false)
            ->assertSee('class="sgh-filter-actions"', false)
            ->assertSee('Apply filters')
            ->assertSee('Reset all');
    }

    public function test_category_names_are_never_run_as_code(): void
    {
        $this->makeCategory('<b>Evil</b>', 'evil');

        $this->get('/shop')->assertOk()
            ->assertDontSee('<b>Evil</b>', false)
            ->assertSee('&lt;b&gt;Evil&lt;/b&gt;', false);
    }

    public function test_filter_chips_and_the_empty_state_still_work(): void
    {
        $this->get('/shop?min=100')->assertOk()->assertSee('Min 100');
        $this->get('/shop?min=99999999')->assertOk()->assertSee('No products found');
    }

    public function test_the_page_has_its_own_address_and_keeps_filtered_copies_out_of_search_engines(): void
    {
        $this->get('/shop')->assertOk()
            ->assertSee('<link rel="canonical" href="' . url('/shop') . '">', false)
            ->assertDontSee('name="robots"', false);

        $this->get('/shop?min=100&sort=price_asc')->assertOk()
            ->assertSee('<link rel="canonical" href="' . url('/shop') . '">', false)
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_every_page_measures_the_header_so_the_sticky_panel_never_goes_off_screen(): void
    {
        $this->get('/shop')->assertOk()->assertSee('assets/js/layout.js', false);

        $script = file_get_contents(public_path('assets/js/layout.js'));
        $this->assertStringContainsString('--sgh-header-h', $script);
        $this->assertStringContainsString('ResizeObserver', $script);
    }
}
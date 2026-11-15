<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendedDesignTest extends TestCase
{
    use RefreshDatabase;

    // (The helper name must not clash with Laravel's own get(), post()... test methods.)
    private function makeLink(array $o = []): AffiliateLink
    {
        return AffiliateLink::create(array_merge([
            'name'     => 'Cordless Drill',
            'merchant' => 'Daraz',
            'slug'     => 'cordless-drill-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5)),
            'url'      => 'https://www.daraz.pk/products/cordless-drill-i123.html',
            'status'   => 'active',
        ], $o));
    }

    public function test_the_full_page_has_the_notes_the_steps_and_the_closing_band(): void
    {
        $this->makeLink();

        $html = $this->get('/recommended')->assertOk()
            ->assertSee('Useful extras')
            ->assertSee('Check the price')
            ->assertSee('How these picks work')
            ->assertSee('We choose')
            ->assertSee('You visit the store')
            ->assertSee('The store may pay us')
            ->assertSee('Looking for pieces for your walls?')
            ->assertSee('Browse the shop')
            ->assertSee('Try the Décor Helper')
            ->assertSee('href="' . route('helper') . '"', false)
            ->getContent();

        // The disclosure is linked from the notice and again from the steps
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="' . route('legal.affiliate') . '"'));
    }

    public function test_an_empty_page_has_none_of_it(): void
    {
        $this->get('/recommended')->assertOk()
            ->assertSee('No recommendations yet')
            ->assertDontSee('Useful extras')
            ->assertDontSee('How these picks work')
            ->assertDontSee('Looking for pieces for your walls?')
            ->assertDontSee('sgh-rec-featured', false);
    }

    public function test_a_search_or_a_section_filter_shows_only_the_results(): void
    {
        $this->makeLink(['section' => 'Decorative Lighting']);

        foreach (['/recommended?q=drill', '/recommended?section=' . urlencode('Decorative Lighting')] as $uri) {
            $this->get($uri)->assertOk()
                ->assertSee('Cordless Drill')
                ->assertDontSee('Useful extras')
                ->assertDontSee('How these picks work')
                ->assertDontSee('Looking for pieces for your walls?');
        }
    }

    public function test_the_featured_band_appears_only_when_a_link_is_featured_and_nobody_is_searching(): void
    {
        $this->makeLink(['name' => 'Plain Thing']);
        $this->get('/recommended')->assertOk()->assertDontSee('sgh-rec-featured', false)->assertDontSee('Featured picks');

        $this->makeLink(['name' => 'Star Thing', 'is_featured' => true]);
        $this->get('/recommended')->assertOk()->assertSee('sgh-rec-featured', false)->assertSee('Featured picks');

        $this->get('/recommended?q=thing')->assertOk()->assertDontSee('sgh-rec-featured', false);
    }

    public function test_each_section_shows_how_many_picks_it_has(): void
    {
        $this->makeLink(['name' => 'Lamp One', 'section' => 'Decorative Lighting']);
        $this->makeLink(['name' => 'Lamp Two', 'section' => 'Decorative Lighting']);
        $this->makeLink(['name' => 'Clock One', 'section' => 'Designer Wall Clocks']);

        $this->get('/recommended')->assertOk()
            ->assertSee('class="sgh-rec-count"', false)
            ->assertSee('2 picks')
            ->assertSee('1 pick')
            ->assertDontSee('1 picks');
    }

    public function test_the_cards_lift_and_never_show_a_price(): void
    {
        $this->makeLink();

        $this->get('/recommended')->assertOk()
            ->assertSee('card sgh-affiliate sgh-lift', false)
            ->assertSee('Check the current price and details on Daraz.')
            ->assertSee('(affiliate link, opens in a new tab)')
            ->assertSee('rel="sponsored nofollow noopener"', false);
    }
}
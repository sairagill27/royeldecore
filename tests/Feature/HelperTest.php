<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelperTest extends TestCase
{
    use RefreshDatabase;

    // A product that can be bought, with a photo and no description
    private function piece(array $o = [])
    {
        return $this->makeProduct(array_merge(['stock' => 5, 'description' => '', 'image' => 'products/x.jpg'], $o));
    }

    private function ask(string $query = 'go=1&room=living&style=islamic&budget=any')
    {
        return $this->get('/helper?' . $query);
    }

    // ---------- the page ----------

    public function test_the_page_opens_with_the_form_and_is_open_to_search_engines(): void
    {
        $this->get('/helper')->assertOk()
            ->assertSee('class="sgh-tool-head"', false)
            ->assertSee('Décor Helper')
            ->assertSee('Which room?')
            ->assertSee('Living room')
            ->assertSee('Islamic and traditional')
            ->assertSee('Any budget')
            ->assertSee('name="room"', false)
            ->assertSee('name="style"', false)
            ->assertSee('name="budget"', false)
            ->assertSee('<link rel="canonical" href="' . route('helper') . '">', false)
            ->assertDontSee('noindex', false)
            ->assertSee('not an AI');
    }

    public function test_budget_labels_use_the_shop_currency(): void
    {
        config(['shop.currency' => 'Rs.']);

        $this->get('/helper')->assertOk()
            ->assertSee('Under Rs. 5,000')
            ->assertSee('Rs. 5,000 to 15,000')
            ->assertSee('Over Rs. 15,000');
    }

    // ---------- the matching ----------

    public function test_best_matches_come_first_and_a_mood_board_is_shown(): void
    {
        $this->piece(['name' => 'Plain Vase']);
        $this->piece(['name' => 'Round Wall Mirror']);
        $this->piece(['name' => 'Islamic Calligraphy Wall Art']);

        $this->ask()->assertOk()
            ->assertSee('Your mood board')
            ->assertSee('sgh-board-tile', false)
            ->assertSeeInOrder(['Islamic Calligraphy Wall Art', 'Round Wall Mirror', 'Plain Vase'])
            ->assertSee('noindex', false);
    }

    public function test_a_word_matches_its_plural_but_not_the_middle_of_another_word(): void
    {
        $this->piece(['name' => 'Gold Mirrors Set']);       // "mirror" finds "Mirrors", "gold" finds "Gold"
        $this->piece(['name' => 'Handled Basket']);          // "led" must not match inside "Handled"

        $this->ask('go=1&room=bedroom&style=golden')->assertOk()
            ->assertSeeInOrder(['Gold Mirrors Set', 'Handled Basket']);
    }

    public function test_the_budget_filters_the_pieces(): void
    {
        $this->piece(['name' => 'Cheap Clock', 'price' => 2000]);
        $this->piece(['name' => 'Costly Mirror', 'price' => 20000]);

        $this->ask('go=1&room=living&style=modern&budget=low')->assertOk()
            ->assertSee('Cheap Clock')
            ->assertDontSee('Costly Mirror');

        $this->ask('go=1&room=living&style=modern&budget=high')->assertOk()
            ->assertSee('Costly Mirror')
            ->assertDontSee('Cheap Clock');
    }

    public function test_drafts_and_sold_out_pieces_are_never_suggested(): void
    {
        $this->piece(['name' => 'Draft Clock', 'status' => 'draft']);
        $this->piece(['name' => 'Sold Out Clock', 'stock' => 0]);
        $this->piece(['name' => 'Available Clock']);

        $this->ask('go=1&room=living&style=modern')->assertOk()
            ->assertSee('Available Clock')
            ->assertDontSee('Draft Clock')
            ->assertDontSee('Sold Out Clock');
    }

    public function test_when_nothing_matches_it_says_so_and_still_shows_pieces(): void
    {
        $this->piece(['name' => 'Plain Vase']);

        $this->ask('go=1&room=kids&style=natural')->assertOk()
            ->assertSee('could not find exact matches')
            ->assertSee('Plain Vase');
    }

    public function test_with_no_pieces_at_all_it_is_friendly(): void
    {
        $this->ask()->assertOk()
            ->assertSee('We have no pieces in this budget right now')
            ->assertDontSee('Your mood board');
    }

    public function test_the_mood_board_has_at_most_four_pictures(): void
    {
        foreach (range(1, 6) as $n) {
            $this->piece(['name' => 'Islamic Calligraphy Piece ' . $n]);
        }

        $html = $this->ask()->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, 'sgh-board-tile'));
    }

    public function test_pieces_without_photos_are_listed_but_not_on_the_board(): void
    {
        $this->piece(['name' => 'Islamic Calligraphy No Photo', 'image' => null]);

        $this->ask()->assertOk()
            ->assertSee('Islamic Calligraphy No Photo')
            ->assertDontSee('sgh-board-tile', false)
            ->assertSee('have no photos yet');
    }

    // ---------- links and next steps ----------

    public function test_it_links_to_the_right_calculator_and_to_whatsapp_only_with_a_valid_number(): void
    {
        $this->piece(['name' => 'Islamic Calligraphy Art']);

        config(['contact.whatsapp' => null]);
        $this->ask()->assertOk()
            ->assertSee(route('tools.show', 'paint-calculator'), false)
            ->assertDontSee('Ask us on WhatsApp');

        config(['contact.whatsapp' => '+92 300-1234567']);
        $this->ask()->assertOk()
            ->assertSee('Ask us on WhatsApp')
            ->assertSee('https://wa.me/923001234567?text=', false);
    }

    // ---------- safety ----------

    public function test_odd_and_unknown_values_are_ignored_and_never_echoed(): void
    {
        $this->get('/helper?go=1&room=hack&style=' . urlencode('<script>alert(1)</script>'))->assertOk()
            ->assertSee('Please choose a room and a style.')
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get('/helper?go=1&room[]=x&style[]=y&budget[]=z')->assertOk()
            ->assertSee('Please choose a room and a style.');

        $this->get('/helper?room=living')->assertOk()
            ->assertDontSee('Please choose a room and a style.');
    }

    public function test_a_product_name_cannot_run_as_code_on_the_result_page(): void
    {
        $this->piece(['name' => '<script>alert(1)</script> Islamic Calligraphy']);

        $this->ask()->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- where the helper is linked from ----------

    public function test_the_helper_is_in_the_menu_the_sitemap_and_the_home_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Décor Helper')
            ->assertSee('href="' . url('/helper') . '"', false)
            ->assertSee('Not sure where to start?')
            ->assertSee('Open the Décor Helper')
            ->assertSee(route('helper', ['room' => 'living']), false);

        $this->get('/sitemap.xml')->assertOk()->assertSee(route('helper'), false);
    }

    public function test_the_home_page_has_the_wave_the_fade_up_script_and_one_divider_style(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('sgh-rule', $html);   // the shared heading style draws the divider now

        $script = file_get_contents(public_path('assets/js/layout.js'));
        $this->assertStringContainsString('IntersectionObserver', $script);
        $this->assertStringContainsString('prefers-reduced-motion', $script);
        $this->assertStringContainsString('--sgh-header-h', $script);   // the earlier header fix is still there

        $css = file_get_contents(public_path('assets/css/style.css'));
        $this->assertStringContainsString('.sgh-section-title::after', $css);
        $this->assertStringContainsString('.sgh-cats::before', $css);
    }
}
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolsTest extends TestCase
{
    use RefreshDatabase;

    // A normal paint request: a 4 x 5 m room, 2.5 m walls, 1 door, 2 windows, 2 coats, 10 m2 per litre, 4 L tins
    private const PAINT = [
        'unit' => 'm', 'length' => 4, 'width' => 5, 'height' => 2.5, 'doors' => 1, 'windows' => 2,
        'coats' => 2, 'coverage' => 10, 'tin' => 4, 'ceiling' => 0,
    ];

    // A normal flooring request: a 4 x 5 m room, 10% waste, 2.2 m2 packs at 3500 each
    private const FLOOR = ['unit' => 'm', 'length' => 4, 'width' => 5, 'waste' => 10, 'pack' => 2.2, 'price' => 3500];

    // Sends a request the way the live page does and returns the results as ['label' => 'value']
    private function answer(string $tool, array $data): array
    {
        $response = $this->postJson(route('tools.calculate', $tool), $data)->assertOk();

        return collect($response->json('results'))->pluck('value', 'label')->all();
    }

    // ---------- the pages ----------

    public function test_the_tools_page_lists_every_calculator(): void
    {
        $this->get(route('tools.index'))->assertOk()
            ->assertSee('Paint Calculator')
            ->assertSee('Flooring Calculator')
            ->assertSee('Wallpaper Calculator')
            ->assertSee('Curtain Calculator')
            ->assertSee('Room Budget Planner')
            ->assertSee('Coming soon')
            ->assertSee('href="' . route('tools.show', 'paint-calculator') . '"', false);
    }

    public function test_a_calculator_page_has_its_form_and_the_search_engine_basics(): void
    {
        $this->get(route('tools.show', 'paint-calculator'))->assertOk()
            ->assertSee('data-tool-form', false)
            ->assertSee('name="unit"', false)
            ->assertSee('name="length"', false)
            ->assertSee('<link rel="canonical" href="' . route('tools.show', 'paint-calculator') . '">', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('assets/js/tools.js', false)
            ->assertSee('estimates');
    }

    public function test_a_calculator_that_is_not_built_yet_shows_a_friendly_page_not_a_404(): void
    {
        $this->get(route('tools.show', 'wallpaper-calculator'))->assertOk()
            ->assertSee('coming soon')
            ->assertSee('noindex', false);

        $this->postJson(route('tools.calculate', 'wallpaper-calculator'), [])->assertNotFound();
    }

    public function test_unknown_or_odd_tool_addresses_show_404(): void
    {
        $this->get('/tools/nothing-here')->assertNotFound();
        $this->get('/tools/Paint-Calculator')->assertNotFound();
        $this->postJson('/tools/nothing-here', self::PAINT)->assertNotFound();
    }

    // ---------- paint ----------

    public function test_paint_in_metres(): void
    {
        $values = $this->answer('paint-calculator', self::PAINT);

        $this->assertSame('40.1 m²', $values['Area to paint']);       // 45 - 1.9 - 3.0
        $this->assertSame('8.9 litres', $values['Paint needed']);     // 40.1 x 2 / 10 x 1.1, rounded up
        $this->assertSame('3 tins of 4 L', $values['Tins to buy']);
    }

    public function test_paint_with_the_ceiling(): void
    {
        $values = $this->answer('paint-calculator', array_merge(self::PAINT, ['ceiling' => 1]));

        $this->assertSame('60.1 m²', $values['Area to paint']);
        $this->assertSame('13.3 litres', $values['Paint needed']);
    }

    public function test_paint_in_feet(): void
    {
        $values = $this->answer('paint-calculator', [
            'unit' => 'ft', 'length' => 10, 'width' => 12, 'height' => 9, 'doors' => 0, 'windows' => 0,
            'coats' => 1, 'coverage' => 108, 'tin' => 4,
        ]);

        $this->assertSame('396 ft²', $values['Area to paint']);       // 2 x (10 + 12) x 9
        $this->assertSame('4.1 litres', $values['Paint needed']);
    }

    public function test_paint_without_doors_and_windows_boxes_counts_them_as_none(): void
    {
        $data = self::PAINT;
        unset($data['doors'], $data['windows'], $data['ceiling']);

        $values = $this->answer('paint-calculator', $data);

        $this->assertSame('45 m²', $values['Area to paint']);
        $this->assertSame('9.9 litres', $values['Paint needed']);     // 9.9 must not become 10.0 by rounding error
    }

    public function test_paint_checks_every_value(): void
    {
        $bad = [
            ['length' => null], ['length' => 'abc'], ['length' => 0], ['length' => -3], ['length' => 101],
            ['width' => null], ['height' => 0.5], ['height' => 7],
            ['doors' => 25], ['doors' => -1], ['doors' => 1.5], ['windows' => 31],
            ['coats' => 9], ['coats' => 0], ['coverage' => 0], ['coverage' => 'x'],
            ['tin' => 0.1], ['tin' => 99], ['unit' => 'yards'], ['unit' => null],
        ];

        foreach ($bad as $change) {
            $this->postJson(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, $change))
                ->assertStatus(422)
                ->assertJsonValidationErrors(array_key_first($change));
        }
    }

    public function test_odd_input_types_are_refused_and_never_crash(): void
    {
        $this->postJson(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, ['length' => ['1', '2']]))
            ->assertStatus(422)->assertJsonValidationErrors('length');

        $this->postJson(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, ['unit' => ['ft']]))
            ->assertStatus(422)->assertJsonValidationErrors('unit');
    }

    public function test_doors_and_windows_cannot_be_bigger_than_the_walls(): void
    {
        $this->postJson(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, [
            'length' => 2, 'width' => 2, 'height' => 2, 'doors' => 10, 'windows' => 0,
        ]))->assertStatus(422)->assertJsonValidationErrors('doors');
    }

    // ---------- flooring ----------

    public function test_flooring_with_packs_and_a_price(): void
    {
        config(['shop.currency' => 'Rs.']);

        $values = $this->answer('flooring-calculator', self::FLOOR);

        $this->assertSame('20 m²', $values['Room area']);
        $this->assertSame('22 m²', $values['Area to buy']);            // 20 plus 10%
        $this->assertSame('10 packs', $values['Packs to buy']);        // 22 / 2.2 must be 10, not 11
        $this->assertSame('Rs. 35,000', $values['Estimated cost']);
    }

    public function test_flooring_without_a_pack_size_gives_the_area_only(): void
    {
        $values = $this->answer('flooring-calculator', ['unit' => 'm', 'length' => 4, 'width' => 5, 'price' => 3500]);

        $this->assertSame('22 m²', $values['Area to buy']);            // the waste defaults to 10%
        $this->assertArrayNotHasKey('Packs to buy', $values);
        $this->assertArrayNotHasKey('Estimated cost', $values);        // a price needs the pack size
    }

    public function test_flooring_waste_and_feet(): void
    {
        $values = $this->answer('flooring-calculator', ['unit' => 'ft', 'length' => 10, 'width' => 12, 'waste' => 0]);

        $this->assertSame('120 ft²', $values['Room area']);
        $this->assertSame('120 ft²', $values['Area to buy']);
    }

    public function test_flooring_checks_every_value(): void
    {
        $bad = [
            ['length' => null], ['width' => 0], ['waste' => 45], ['waste' => -1],
            ['pack' => 0], ['pack' => 99], ['price' => -5], ['unit' => 'x'],
        ];

        foreach ($bad as $change) {
            $this->postJson(route('tools.calculate', 'flooring-calculator'), array_merge(self::FLOOR, $change))
                ->assertStatus(422)
                ->assertJsonValidationErrors(array_key_first($change));
        }
    }

    // ---------- the page without JavaScript ----------

    public function test_sending_the_form_without_javascript_shows_the_answer_on_the_page(): void
    {
        $this->followingRedirects()
            ->post(route('tools.calculate', 'paint-calculator'), self::PAINT)
            ->assertOk()
            ->assertSee('8.9 litres')
            ->assertSee('3 tins of 4 L');
    }

    public function test_a_mistake_without_javascript_sends_you_back_with_the_message(): void
    {
        $this->from(route('tools.show', 'paint-calculator'))
            ->post(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, ['length' => 0]))
            ->assertRedirect(route('tools.show', 'paint-calculator'))
            ->assertSessionHasErrors('length');
    }

    public function test_what_you_typed_is_never_run_as_code_when_it_comes_back(): void
    {
        $this->followingRedirects()
            ->from(route('tools.show', 'paint-calculator'))
            ->post(route('tools.calculate', 'paint-calculator'), array_merge(self::PAINT, ['length' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------- the look ----------

    public function test_the_tool_header_and_result_card_use_the_shops_own_dark_green(): void
    {
        $this->get(route('tools.show', 'paint-calculator'))->assertOk()
            ->assertSee('class="sgh-tool-head"', false)
            ->assertSee('sgh-tool-leaves', false)
            ->assertSee('class="sgh-result-card"', false)
            ->assertSee('class="sgh-step"', false)
            ->assertSee('Paint Calculator')
            ->assertSee('Litres of paint')          // the "what you will get" list
            ->assertSee('How we calculate');
    }

    public function test_the_tools_page_and_the_coming_soon_page_use_the_same_header(): void
    {
        $this->get(route('tools.index'))->assertOk()->assertSee('class="sgh-tool-head"', false);
        $this->get(route('tools.show', 'curtain-calculator'))->assertOk()->assertSee('class="sgh-tool-head"', false);
    }

    public function test_every_tool_has_a_short_what_you_get_list(): void
    {
        foreach (config('tools') as $tool) {
            $this->assertNotEmpty($tool['gets']);
        }
    }
}
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
            ->assertDontSee('Coming soon')
            ->assertSee('href="' . route('tools.show', 'wallpaper-calculator') . '"', false);
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

    public function test_every_calculator_page_opens_and_none_shows_a_coming_soon_page(): void
    {
        foreach (array_keys(config('tools')) as $slug) {
            $this->get(route('tools.show', $slug))->assertOk()->assertDontSee('coming soon');
        }
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

    // ---------- wallpaper ----------

    // A 4 x 5 m room, 2.5 m walls, 1 door and 2 windows, a 0.53 x 10 m roll and no pattern repeat
    private const WALL = [
        'unit' => 'm', 'length' => 4, 'width' => 5, 'height' => 2.5, 'doors' => 1, 'windows' => 2,
        'rollw' => 0.53, 'rolll' => 10, 'repeat' => 0,
    ];

    public function test_wallpaper_rolls(): void
    {
        $values = $this->answer('wallpaper-calculator', self::WALL);

        // 4 strips per roll (10 / 2.5). The 18 m around the room, less 3.3 m for the doors and windows,
        // gives 28 strips (14.7 / 0.53 rounded up), which is 7 rolls.
        $this->assertSame('7 rolls', $values['Rolls to buy']);
        $this->assertSame('40.1 m²', $values['Wall area']);
    }

    public function test_a_pattern_repeat_makes_each_strip_longer_and_needs_more_rolls(): void
    {
        $plain   = (int) $this->answer('wallpaper-calculator', self::WALL)['Rolls to buy'];
        $pattern = (int) $this->answer('wallpaper-calculator', array_merge(self::WALL, ['repeat' => 0.64]))['Rolls to buy'];

        $this->assertGreaterThan($plain, $pattern);
    }

    public function test_wallpaper_shows_a_cost_only_when_a_price_is_given(): void
    {
        config(['shop.currency' => 'Rs.']);

        $this->assertArrayNotHasKey('Estimated cost', $this->answer('wallpaper-calculator', self::WALL));
        $this->assertSame('Rs. 21,000', $this->answer('wallpaper-calculator', array_merge(self::WALL, ['price' => 3000]))['Estimated cost']);
    }

    public function test_a_roll_too_short_for_one_strip_is_explained_not_divided_by_zero(): void
    {
        $this->postJson(route('tools.calculate', 'wallpaper-calculator'), array_merge(self::WALL, ['rolll' => 2]))
            ->assertStatus(422)->assertJsonValidationErrors('rolll');
    }

    public function test_wallpaper_checks_every_value(): void
    {
        $bad = [
            ['length' => 0], ['width' => null], ['height' => 9], ['rollw' => 0], ['rollw' => 3],
            ['rolll' => 0], ['rolll' => 500], ['repeat' => -1], ['repeat' => 3], ['doors' => 99], ['unit' => 'z'],
        ];

        foreach ($bad as $change) {
            $this->postJson(route('tools.calculate', 'wallpaper-calculator'), array_merge(self::WALL, $change))
                ->assertStatus(422)->assertJsonValidationErrors(array_key_first($change));
        }
    }

    public function test_wallpaper_in_feet(): void
    {
        $values = $this->answer('wallpaper-calculator', [
            'unit' => 'ft', 'length' => 13, 'width' => 16, 'height' => 8, 'doors' => 0, 'windows' => 0,
            'rollw' => 1.75, 'rolll' => 33, 'repeat' => 0,
        ]);

        $this->assertSame('4 strips per roll', trim(explode(',', $this->noteOf('wallpaper-calculator', [
            'unit' => 'ft', 'length' => 13, 'width' => 16, 'height' => 8, 'doors' => 0, 'windows' => 0,
            'rollw' => 1.75, 'rolll' => 33, 'repeat' => 0,
        ], 'Rolls to buy'))[1]));
        $this->assertSame('464 ft²', $values['Wall area']);   // 2 x (13 + 16) x 8
    }

    // ---------- curtains ----------

    public function test_curtain_fabric_and_panels(): void
    {
        $values = $this->answer('curtain-calculator', ['unit' => 'm', 'width' => 2, 'drop' => 2.2, 'fullness' => 2, 'panelw' => 1.4]);

        $this->assertSame('3 panels', $values['Panels needed']);              // 4 m of fabric / 1.4 m = 3 panels
        $this->assertSame('7.5 m', $values['Fabric to buy']);                 // (2.2 + 0.3) x 3
        $this->assertSame('4 m', $values['Fabric width across the window']);
    }

    public function test_more_fullness_needs_more_panels(): void
    {
        $light  = (int) $this->answer('curtain-calculator', ['unit' => 'm', 'width' => 3, 'drop' => 2, 'fullness' => 1.5, 'panelw' => 1.4])['Panels needed'];
        $luxury = (int) $this->answer('curtain-calculator', ['unit' => 'm', 'width' => 3, 'drop' => 2, 'fullness' => 2.5, 'panelw' => 1.4])['Panels needed'];

        $this->assertGreaterThan($light, $luxury);
    }

    public function test_curtains_in_feet_show_yards_of_fabric(): void
    {
        $values = $this->answer('curtain-calculator', ['unit' => 'ft', 'width' => 6, 'drop' => 7, 'fullness' => 2, 'panelw' => 4.6]);

        $this->assertStringContainsString('yards', $values['Fabric to buy']);
        $this->assertStringContainsString('ft', $values['Fabric width across the window']);
    }

    public function test_curtains_check_every_value(): void
    {
        $base = ['unit' => 'm', 'width' => 2, 'drop' => 2.2, 'fullness' => 2, 'panelw' => 1.4];
        $bad  = [['width' => 0], ['drop' => null], ['drop' => 9], ['fullness' => 3], ['fullness' => 'lots'], ['panelw' => 0], ['unit' => 'x']];

        foreach ($bad as $change) {
            $this->postJson(route('tools.calculate', 'curtain-calculator'), array_merge($base, $change))
                ->assertStatus(422)->assertJsonValidationErrors(array_key_first($change));
        }
    }

    // ---------- room budget ----------

    public function test_budget_total_and_money_left(): void
    {
        config(['shop.currency' => 'Rs.']);

        $values = $this->answer('room-budget-planner', [
            'budget' => 100000,
            'items'  => [['name' => 'Sofa', 'cost' => 60000], ['name' => 'Rug', 'cost' => 15000], ['name' => '', 'cost' => '']],
        ]);

        $this->assertSame('Rs. 75,000', $values['Total spend']);
        $this->assertSame('Rs. 25,000', $values['Money left']);
        $this->assertSame('Rs. 60,000', $values['Sofa']);
        $this->assertArrayNotHasKey('Over budget by', $values);
    }

    public function test_budget_says_when_you_are_over(): void
    {
        config(['shop.currency' => 'Rs.']);

        $values = $this->answer('room-budget-planner', [
            'budget' => 50000,
            'items'  => [['name' => 'Sofa', 'cost' => 60000]],
        ]);

        $this->assertSame('Rs. 10,000', $values['Over budget by']);
        $this->assertArrayNotHasKey('Money left', $values);
    }

    public function test_budget_without_a_budget_amount_gives_the_total_and_shares(): void
    {
        $response = $this->postJson(route('tools.calculate', 'room-budget-planner'), [
            'items' => [['name' => 'Lamp', 'cost' => 1000], ['name' => 'Vase', 'cost' => 3000]],
        ])->assertOk();

        $labels = collect($response->json('results'))->pluck('label')->all();
        $this->assertContains('Total spend', $labels);
        $this->assertNotContains('Money left', $labels);
        $this->assertSame('75% of your total', collect($response->json('results'))->firstWhere('label', 'Vase')['note']);
    }

    public function test_budget_names_are_plain_text_and_empty_names_become_item(): void
    {
        $response = $this->postJson(route('tools.calculate', 'room-budget-planner'), [
            'items' => [['name' => '<script>alert(1)</script>', 'cost' => 500], ['name' => '', 'cost' => 100]],
        ])->assertOk();

        $labels = collect($response->json('results'))->pluck('label')->all();
        $this->assertContains('Item', $labels);
        $this->assertContains('<script>alert(1)</script>', $labels);    // kept as text in the data...

        // ...but never printed as code on the page
        $this->followingRedirects()
            ->post(route('tools.calculate', 'room-budget-planner'), ['items' => [['name' => '<script>alert(1)</script>', 'cost' => 500]]])
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_budget_needs_at_least_one_cost_and_checks_every_value(): void
    {
        $url = route('tools.calculate', 'room-budget-planner');

        $this->postJson($url, ['items' => [['name' => 'Sofa', 'cost' => '']]])->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson($url, ['items' => [['name' => 'Sofa', 'cost' => 0]]])->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson($url, ['items' => 'lots'])->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson($url, ['items' => [['name' => 'Sofa', 'cost' => -5]]])->assertStatus(422)->assertJsonValidationErrors('items.0.cost');
        $this->postJson($url, ['items' => [['name' => 'Sofa', 'cost' => 'abc']]])->assertStatus(422)->assertJsonValidationErrors('items.0.cost');
        $this->postJson($url, ['budget' => -1, 'items' => [['name' => 'Sofa', 'cost' => 5]]])->assertStatus(422)->assertJsonValidationErrors('budget');
        $this->postJson($url, ['items' => [['name' => str_repeat('a', 61), 'cost' => 5]]])->assertStatus(422)->assertJsonValidationErrors('items.0.name');
    }

    public function test_budget_accepts_at_most_30_items(): void
    {
        $url   = route('tools.calculate', 'room-budget-planner');
        $items = fn (int $n) => array_fill(0, $n, ['name' => 'Thing', 'cost' => 10]);

        $this->postJson($url, ['items' => $items(30)])->assertOk();
        $this->postJson($url, ['items' => $items(31)])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    // ---------- the sitemap and the home page ----------

    public function test_every_working_tool_is_in_the_sitemap(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString(route('tools.index'), $xml);
        foreach (array_keys(config('tools')) as $slug) {
            $this->assertStringContainsString(route('tools.show', $slug), $xml);
        }
    }

    public function test_the_home_page_links_to_the_tools(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('href="' . route('tools.index') . '"', false)
            ->assertSee('Décor Tools');
    }

    // Returns the note under one result row, for checks on the wording
    private function noteOf(string $tool, array $data, string $label): string
    {
        $rows = $this->postJson(route('tools.calculate', $tool), $data)->assertOk()->json('results');

        return (string) collect($rows)->firstWhere('label', $label)['note'];
    }
}
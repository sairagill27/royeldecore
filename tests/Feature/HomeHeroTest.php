<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hero_has_the_new_wording_and_buttons(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Premium Home Décor')
            ->assertSee('Beautiful Spaces Start with the Right')
            ->assertSee('Transform your home with unique décor pieces, designed to bring warmth, style and inspiration to every corner.')
            ->assertSee('Shop Now')
            ->assertSee('Explore Ideas')
            ->assertSee('href="' . route('shop') . '"', false)
            ->assertSee('href="' . route('blog.index') . '"', false);
    }

    public function test_the_shortcut_row_only_offers_things_that_are_true_for_this_shop(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Pay on delivery')
            ->assertSee('Décor ideas')
            ->assertSee('Browse by category')
            ->assertSee('Track your order')
            ->assertSee('href="' . route('account.orders') . '"', false);
    }

    public function test_the_hero_makes_no_promises_the_shop_has_not_written_down(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('Fast Delivery')
            ->assertDontSee('Easy Returns')
            ->assertDontSee('Secure Shopping')
            ->assertDontSee('Premium Quality')
            ->assertDontSee('Free delivery');
    }

    public function test_the_leaf_drawing_and_the_photo_area_are_on_the_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('sgh-banner-leaves', false)
            ->assertSee('id="sgh-branch"', false)
            ->assertSee('sgh-banner-photo', false);
    }
}
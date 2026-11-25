<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InnerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_use_the_same_header_as_shop_and_blog(): void
    {
        Storage::fake('public');

        foreach (['/about', '/contact'] as $page) {
            $this->get($page)->assertOk()
                ->assertSee('class="sgh-tool-head"', false)
                ->assertSee('sgh-tool-leaves', false)
                ->assertSee('sgh-head-glow', false)
                ->assertSee('class="sgh-tool-head-icon"', false)
                ->assertDontSee('class="sgh-shop-hero"', false);
        }
    }

    public function test_the_two_new_picture_slots_appear_in_the_admin_and_work_on_their_own_page_only(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeStaff())->get(route('admin.page-pictures.index'))->assertOk()
            ->assertSee('About page')->assertSee('Contact page');

        auth()->logout();

        $this->get('/about')->assertOk()->assertDontSee('sgh-head-photo', false);

        Storage::disk('public')->put('page-headers/about.jpg', 'x');

        $this->get('/about')->assertOk()->assertSee('class="sgh-head-photo"', false)->assertSee('page-headers/about.jpg', false);
        $this->get('/contact')->assertOk()->assertDontSee('sgh-head-photo', false);
    }

    public function test_the_contact_form_is_untouched(): void
    {
        $this->get('/contact')->assertOk()
            ->assertSee('action="' . route('contact.store') . '"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="subject"', false)
            ->assertSee('name="message"', false)
            ->assertSee('name="website"', false)       // the spam trap
            ->assertSee('Send message');
    }

    public function test_contact_cards_show_only_valid_details_and_never_run_code(): void
    {
        config(['contact.whatsapp' => null, 'contact.phone' => null, 'contact.address' => null]);
        $this->get('/contact')->assertOk()->assertDontSee('wa.me', false)->assertDontSee('tel:', false)->assertDontSee('Visit us');

        config(['contact.whatsapp' => '123', 'contact.phone' => 'abc']);
        $this->get('/contact')->assertOk()->assertDontSee('wa.me', false)->assertDontSee('tel:', false);

        config(['contact.whatsapp' => '+92 300-1234567', 'contact.phone' => '+92 339 2629661', 'contact.address' => '<script>alert(1)</script> 12 Main Road']);
        $this->get('/contact')->assertOk()
            ->assertSee('https://wa.me/923001234567?text=', false)
            ->assertSee('tel:+923392629661', false)
            ->assertSee('Visit us')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_contact_page_links_to_the_assistant(): void
    {
        $this->get('/contact')->assertOk()->assertSee('Ask the Décor Assistant')->assertSee(route('assistant'), false);
    }

    public function test_the_about_page_has_the_explore_row_and_the_call_to_action_band(): void
    {
        $this->get('/about')->assertOk()
            ->assertSee('Explore ' . config('app.name'))
            ->assertSee('Read the journal')
            ->assertSee(route('tools.index'), false)
            ->assertSee(route('helper'), false)
            ->assertSee('class="sgh-cta-band text-center"', false)
            ->assertSee('Ready to refresh your space?');
    }
}
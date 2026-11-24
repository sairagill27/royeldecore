<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagePictureTest extends TestCase
{
    use RefreshDatabase;

    private function needGd(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
    }

    private function send($user, string $key, array $data)
    {
        return $this->actingAs($user)->post(route('admin.page-pictures.update', $key), $data);
    }

    public function test_staff_see_every_page_with_its_picture_idea(): void
    {
        Storage::fake('public');

        $this->actingAs($this->makeStaff())->get(route('admin.page-pictures.index'))->assertOk()
            ->assertSee('Header pictures')
            ->assertSee('Shop page')
            ->assertSee('Blog page')
            ->assertSee('Décor tools page')
            ->assertSee('Décor Helper page')
            ->assertSee('Assistant page')
            ->assertSee('Recommended page')
            ->assertSee('No picture yet')
            ->assertSee('Keep the right side');
    }

    public function test_a_picture_is_cropped_saved_as_a_real_jpeg_and_shown_on_its_page_only(): void
    {
        $this->needGd();
        Storage::fake('public');

        $this->send($this->makeStaff(), 'shop', ['picture' => UploadedFile::fake()->image('wide.jpg', 2400, 1000), 'focus' => 'right'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.page-pictures.index'));

        Storage::disk('public')->assertExists('page-headers/shop.jpg');

        $info = getimagesizefromstring(Storage::disk('public')->get('page-headers/shop.jpg'));
        $this->assertSame((int) config('pagepictures.width'), $info[0]);
        $this->assertSame((int) config('pagepictures.height'), $info[1]);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);

        $this->get('/shop')->assertOk()
            ->assertSee('class="sgh-head-photo"', false)
            ->assertSee('storage/page-headers/shop.jpg', false);

        $this->get('/blog')->assertOk()->assertDontSee('sgh-head-photo', false);
        $this->get('/')->assertOk()->assertDontSee('sgh-head-photo', false);
    }

    public function test_a_tall_png_is_accepted_too_and_each_focus_choice_works(): void
    {
        $this->needGd();
        Storage::fake('public');
        $staff = $this->makeStaff();

        foreach (['left', 'center', 'right'] as $focus) {
            $this->send($staff, 'blog', ['picture' => UploadedFile::fake()->image('tall.png', 1200, 1800), 'focus' => $focus])
                ->assertSessionHasNoErrors();
        }

        $info = getimagesizefromstring(Storage::disk('public')->get('page-headers/blog.jpg'));
        $this->assertSame([(int) config('pagepictures.width'), (int) config('pagepictures.height')], [$info[0], $info[1]]);
    }

    public function test_a_new_picture_replaces_the_old_one(): void
    {
        $this->needGd();
        Storage::fake('public');
        $staff = $this->makeStaff();

        $this->send($staff, 'tools', ['picture' => UploadedFile::fake()->image('one.jpg', 2000, 900)]);
        $this->send($staff, 'tools', ['picture' => UploadedFile::fake()->image('two.jpg', 2000, 900)]);

        $this->assertSame(['page-headers/tools.jpg'], Storage::disk('public')->allFiles());
    }

    public function test_files_that_are_not_pictures_or_are_too_small_or_too_big_are_refused(): void
    {
        $this->needGd();
        Storage::fake('public');
        $staff = $this->makeStaff();

        foreach ([
            UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
            UploadedFile::fake()->image('tiny.jpg', 400, 200),
            UploadedFile::fake()->image('huge.jpg', 2000, 900)->size(5000),
        ] as $file) {
            $this->send($staff, 'shop', ['picture' => $file])->assertSessionHasErrorsIn('slot-shop', 'picture');
        }

        $this->send($staff, 'shop', [])->assertSessionHasErrorsIn('slot-shop', 'picture');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_only_left_middle_or_right_are_accepted_for_the_crop(): void
    {
        $this->needGd();
        Storage::fake('public');

        $this->send($this->makeStaff(), 'shop', ['picture' => UploadedFile::fake()->image('wide.jpg', 2000, 900), 'focus' => 'up'])
            ->assertSessionHasErrorsIn('slot-shop', 'focus');

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_an_unknown_page_shows_404(): void
    {
        Storage::fake('public');
        $staff = $this->makeStaff();

        $this->send($staff, 'evil', [])->assertNotFound();
        $this->actingAs($staff)->delete(route('admin.page-pictures.destroy', 'evil'))->assertNotFound();
        $this->send($staff, '..', [])->assertNotFound();
    }

    public function test_a_picture_can_be_removed_and_the_header_goes_back_to_plain_green(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('page-headers/shop.jpg', 'x');

        $this->get('/shop')->assertOk()->assertSee('class="sgh-head-photo"', false);

        $this->actingAs($this->makeStaff())->delete(route('admin.page-pictures.destroy', 'shop'))
            ->assertRedirect(route('admin.page-pictures.index'));

        Storage::disk('public')->assertMissing('page-headers/shop.jpg');

        auth()->logout();
        $this->get('/shop')->assertOk()->assertDontSee('sgh-head-photo', false)->assertSee('sgh-head-glow', false);
    }

    public function test_customers_and_visitors_cannot_use_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('page-headers/shop.jpg', 'x');

        $this->actingAs($this->makeCustomer());
        $this->get(route('admin.page-pictures.index'))->assertForbidden();
        $this->post(route('admin.page-pictures.update', 'shop'), [])->assertForbidden();
        $this->delete(route('admin.page-pictures.destroy', 'shop'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.page-pictures.index'))->assertRedirect(route('login'));
        $this->post(route('admin.page-pictures.update', 'shop'), [])->assertRedirect(route('login'));
        $this->delete(route('admin.page-pictures.destroy', 'shop'))->assertRedirect(route('login'));

        Storage::disk('public')->assertExists('page-headers/shop.jpg');
    }

    public function test_the_menu_links_to_the_page(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Page pictures')
            ->assertSee(route('admin.page-pictures.index'), false);
    }
}
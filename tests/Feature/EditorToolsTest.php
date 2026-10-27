<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Services\Markdown;
use App\Services\SeoScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EditorToolsTest extends TestCase
{
    use RefreshDatabase;

    private function html(string $markdown): string
    {
        return Markdown::article($markdown)['html'];
    }

    // ---------- figures: picture, caption and position ----------

    public function test_a_picture_alone_becomes_a_figure_with_a_caption_and_a_position(): void
    {
        $html = $this->html('![A cosy lamp](/storage/blog/content/a.jpg#left "Warm light at night")');

        $this->assertStringContainsString('<figure class="sgh-figure sgh-figure--left">', $html);
        $this->assertStringContainsString('src="/storage/blog/content/a.jpg"', $html);
        $this->assertStringNotContainsString('#left', $html);
        $this->assertStringContainsString('alt="A cosy lamp"', $html);
        $this->assertStringContainsString('<figcaption>Warm light at night</figcaption>', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_a_picture_without_a_position_is_full_width_and_needs_no_caption(): void
    {
        $html = $this->html('![Plain](/storage/blog/content/b.jpg)');

        $this->assertStringContainsString('sgh-figure--full', $html);
        $this->assertStringNotContainsString('<figcaption>', $html);
    }

    public function test_every_position_is_understood(): void
    {
        foreach (['full', 'center', 'left', 'right'] as $position) {
            $this->assertStringContainsString(
                'sgh-figure--' . $position,
                $this->html("![x](/storage/blog/content/c.jpg#{$position})")
            );
        }
    }

    public function test_a_picture_inside_a_sentence_stays_a_normal_picture(): void
    {
        $html = $this->html('Look at ![x](/storage/blog/content/d.jpg) here.');

        $this->assertStringNotContainsString('<figure', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    public function test_dangerous_addresses_and_html_in_captions_are_made_harmless(): void
    {
        $this->assertStringNotContainsString('javascript:', $this->html('![x](javascript:alert(1))'));

        $html = $this->html('![a](/x.jpg "<b>hi</b>")');
        $this->assertStringNotContainsString('<b>hi</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;hi&lt;/b&gt;', $html);
    }

    public function test_the_editor_preview_shows_figures_too(): void
    {
        $html = $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.posts.preview'), ['content' => '![x](/storage/blog/content/e.jpg#right "Cap")'])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('sgh-figure--right', $html);
        $this->assertStringContainsString('<figcaption>Cap</figcaption>', $html);
    }

    // ---------- uploading pictures for inside an article ----------

    public function test_staff_can_upload_a_picture_and_it_is_saved_under_a_random_name(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
        Storage::fake('public');

        $url = $this->actingAs($this->makeStaff())
            ->postJson(route('admin.posts.images'), ['image' => UploadedFile::fake()->image('my photo.png', 600, 400)])
            ->assertOk()
            ->json('url');

        $this->assertStringStartsWith('/storage/blog/content/', $url);
        $this->assertStringNotContainsString('my photo', $url);
        Storage::disk('public')->assertExists(Str::after($url, '/storage/'));
    }

    public function test_files_that_are_not_jpg_png_or_webp_are_refused(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        foreach ([
            UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
        ] as $file) {
            $this->actingAs($admin)
                ->postJson(route('admin.posts.images'), ['image' => $file])
                ->assertStatus(422)
                ->assertJsonValidationErrors('image');
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_missing_file_is_refused(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson(route('admin.posts.images'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_customers_and_visitors_cannot_upload(): void
    {
        Storage::fake('public');
        $file = fn () => UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');

        $this->actingAs($this->makeCustomer())
            ->postJson(route('admin.posts.images'), ['image' => $file()])
            ->assertForbidden();

        auth()->logout();
        $this->postJson(route('admin.posts.images'), ['image' => $file()])->assertUnauthorized();
    }

    // ---------- the editor page ----------

    public function test_the_editor_has_h2_h3_and_image_buttons_and_the_image_dialog(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.posts.create'))->assertOk()
            ->assertSee('data-md="h2"', false)
            ->assertSee('data-md="h3"', false)
            ->assertSee('data-md="image"', false)
            ->assertSee('id="imageModal"', false)
            ->assertSee('Insert image')
            ->assertSee('data-upload-url="' . route('admin.posts.images') . '"', false);
    }

    public function test_the_save_button_is_at_the_end_of_the_page_and_appears_once(): void
    {
        $html = $this->actingAs($this->makeAdmin())->get(route('admin.posts.create'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Save post'));
        $this->assertGreaterThan(strpos($html, 'Featured image</h2>'), strpos($html, 'class="sgh-form-actions"'));
    }

    // ---------- the SEO score ----------

    public function test_a_picture_does_not_count_as_a_link_to_another_page(): void
    {
        $internal = fn (string $content): bool => collect(
            SeoScore::analyze(['title' => 't', 'slug' => 't', 'content' => $content])['checks']
        )->firstWhere('label', 'Links to another page on your site')['ok'];

        $this->assertFalse($internal('![x](/storage/blog/content/a.jpg)'));
        $this->assertTrue($internal('![x](/storage/blog/content/a.jpg) and [our shop](/shop)'));
    }
}
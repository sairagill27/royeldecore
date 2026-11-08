<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function needGd(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('The PHP "gd" extension is off.');
        }
    }

    private function photos(int $count): array
    {
        $files = [];
        for ($i = 1; $i <= $count; $i++) {
            $files[] = UploadedFile::fake()->image("photo{$i}.jpg", 600, 400);
        }

        return $files;
    }

    private function addPhoto(Product $product, string $path, int $order = 1): ProductImage
    {
        return ProductImage::create(['product_id' => $product->id, 'path' => $path, 'sort_order' => $order]);
    }

    private function upload($user, Product $product, array $photos)
    {
        return $this->actingAs($user)->post(route('admin.products.gallery.store', $product), ['photos' => $photos]);
    }

    private function page(Product $product)
    {
        return $this->get(route('products.show', $product->slug));
    }

    // ---------- adding photos ----------

    public function test_staff_can_add_several_photos_and_each_gets_a_random_name(): void
    {
        $this->needGd();
        Storage::fake('public');
        $product = $this->makeProduct();

        $this->upload($this->makeStaff(), $product, $this->photos(3))->assertSessionHasNoErrors();

        $rows = ProductImage::orderBy('sort_order')->get();
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertStringStartsWith('products/gallery/', $row->path);
            $this->assertStringNotContainsString('photo', $row->path);
            Storage::disk('public')->assertExists($row->path);
        }
        $this->assertSame([1, 2, 3], $rows->pluck('sort_order')->all());
    }

    public function test_files_that_are_not_jpg_png_or_webp_are_refused(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct();
        $admin   = $this->makeAdmin();

        foreach ([
            UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
        ] as $file) {
            $this->upload($admin, $product, [$file])->assertSessionHasErrors('photos.0');
        }

        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_missing_or_odd_value_is_refused(): void
    {
        $product = $this->makeProduct();
        $admin   = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.products.gallery.store', $product), [])
            ->assertSessionHasErrors('photos');
        $this->actingAs($admin)->post(route('admin.products.gallery.store', $product), ['photos' => 'text'])
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_a_product_holds_at_most_eight_extra_photos(): void
    {
        $this->needGd();
        Storage::fake('public');
        $product = $this->makeProduct();
        $admin   = $this->makeAdmin();

        $this->upload($admin, $product, $this->photos(9))->assertSessionHasErrors('photos');
        $this->upload($admin, $product, $this->photos(5))->assertSessionHasNoErrors();
        $this->upload($admin, $product, $this->photos(4))->assertSessionHasErrors('photos');   // only 3 more fit
        $this->upload($admin, $product, $this->photos(3))->assertSessionHasNoErrors();
        $this->upload($admin, $product, $this->photos(1))->assertSessionHasErrors('photos');   // full

        $this->assertDatabaseCount('product_images', 8);
    }

    // ---------- removing photos ----------

    public function test_a_photo_can_be_removed_and_its_file_goes_with_it(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct();
        $other   = $this->makeProduct();
        Storage::disk('public')->put('products/gallery/a.jpg', 'x');
        Storage::disk('public')->put('products/gallery/b.jpg', 'x');
        $mine   = $this->addPhoto($product, 'products/gallery/a.jpg');
        $theirs = $this->addPhoto($other, 'products/gallery/b.jpg');
        $staff  = $this->makeStaff();

        // A photo of another product cannot be removed through this product
        $this->actingAs($staff)->delete(route('admin.products.gallery.destroy', [$product, $theirs]))->assertNotFound();
        $this->assertDatabaseHas('product_images', ['id' => $theirs->id]);
        Storage::disk('public')->assertExists('products/gallery/b.jpg');

        $this->actingAs($staff)->delete(route('admin.products.gallery.destroy', [$product, $mine]))
            ->assertRedirect(route('admin.products.edit', $product));
        $this->assertDatabaseMissing('product_images', ['id' => $mine->id]);
        Storage::disk('public')->assertMissing('products/gallery/a.jpg');
    }

    public function test_deleting_a_product_deletes_its_gallery_photos(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct();
        Storage::disk('public')->put('products/gallery/a.jpg', 'x');
        $this->addPhoto($product, 'products/gallery/a.jpg');

        $this->actingAs($this->makeAdmin())->delete(route('admin.products.destroy', $product))->assertRedirect();

        Storage::disk('public')->assertMissing('products/gallery/a.jpg');
        $this->assertDatabaseCount('product_images', 0);
    }

    // ---------- who can use it ----------

    public function test_customers_and_visitors_cannot_add_or_remove_photos(): void
    {
        Storage::fake('public');
        $product = $this->makeProduct();
        $image   = $this->addPhoto($product, 'products/gallery/a.jpg');
        $file    = fn () => UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg');

        $customer = $this->makeCustomer();
        $this->actingAs($customer)->post(route('admin.products.gallery.store', $product), ['photos' => [$file()]])->assertForbidden();
        $this->actingAs($customer)->delete(route('admin.products.gallery.destroy', [$product, $image]))->assertForbidden();

        auth()->logout();
        $this->post(route('admin.products.gallery.store', $product), ['photos' => [$file()]])->assertRedirect(route('login'));
        $this->delete(route('admin.products.gallery.destroy', [$product, $image]))->assertRedirect(route('login'));

        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_the_edit_page_has_the_gallery_panel_and_the_create_page_does_not(): void
    {
        $product = $this->makeProduct();
        $admin   = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('Photo gallery')
            ->assertSee('No extra photos yet.')
            ->assertSee('action="' . route('admin.products.gallery.store', $product) . '"', false)
            ->assertSee('name="photos[]"', false);

        $image = $this->addPhoto($product, 'products/gallery/a.jpg');

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('storage/products/gallery/a.jpg', false)
            ->assertSee(route('admin.products.gallery.destroy', [$product, $image]), false);

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()
            ->assertDontSee('Photo gallery');
    }

    // ---------- the product page ----------

    public function test_one_photo_shows_no_slider(): void
    {
        $product = $this->makeProduct(['image' => 'products/cover.jpg']);

        $this->page($product)->assertOk()
            ->assertDontSee('id="pdGallery"', false)
            ->assertSee('storage/products/cover.jpg', false);
    }

    public function test_several_photos_show_a_bootstrap_slider_with_thumbnails(): void
    {
        $product = $this->makeProduct(['image' => 'products/cover.jpg']);
        $this->addPhoto($product, 'products/g1.jpg', 1);
        $this->addPhoto($product, 'products/g2.jpg', 2);

        $html = $this->page($product)->assertOk()
            ->assertSee('id="pdGallery"', false)
            ->assertSee('carousel-control-next', false)
            ->assertSee('data-bs-slide-to="2"', false)
            ->assertSee('photo 1 of 3', false)
            ->getContent();

        $this->assertStringNotContainsString('data-bs-slide-to="3"', $html);
        $this->assertSame(3, substr_count($html, 'class="carousel-item'));
    }

    public function test_a_product_with_only_gallery_photos_still_gets_a_slider(): void
    {
        $product = $this->makeProduct(['image' => null]);
        $this->addPhoto($product, 'products/g1.jpg', 1);
        $this->addPhoto($product, 'products/g2.jpg', 2);

        $html = $this->page($product)->assertOk()->assertSee('id="pdGallery"', false)->getContent();

        $this->assertSame(2, substr_count($html, 'class="carousel-item'));
    }

    public function test_every_photo_is_listed_in_the_data_for_search_engines(): void
    {
        $product = $this->makeProduct(['image' => 'products/cover.jpg']);
        $this->addPhoto($product, 'products/g1.jpg', 1);

        $this->page($product)->assertOk()->assertSee(
            '"image":["' . asset('storage/products/cover.jpg') . '","' . asset('storage/products/g1.jpg') . '"]',
            false
        );
    }

    public function test_a_product_name_cannot_run_as_code_in_the_slider(): void
    {
        $product = $this->makeProduct(['name' => '<script>alert(1)</script>', 'image' => 'products/cover.jpg']);
        $this->addPhoto($product, 'products/g1.jpg', 1);

        $this->page($product)->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }
}
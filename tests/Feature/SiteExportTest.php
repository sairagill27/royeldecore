<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteExportTest extends TestCase
{
    use RefreshDatabase;

    private array $folders = [];

    private function folder(): string
    {
        return $this->folders[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sgh-export-' . uniqid();
    }

    protected function tearDown(): void
    {
        foreach ($this->folders as $dir) {
            File::deleteDirectory($dir);
        }

        parent::tearDown();
    }

    private function page(string $out, string $path): string
    {
        $path = trim($path, '/');

        return $out . '/' . ($path === '' ? 'index.html' : $path . '/index.html');
    }

    private function run_export(string $out, array $extra = []): \Illuminate\Testing\PendingCommand
    {
        // The preview address has a folder name on purpose: that is what GitHub Pages gives a project
        return $this->artisan('site:export', array_merge(['--out' => $out, '--base' => 'https://example.github.io/preview'], $extra));
    }

    public function test_the_export_writes_static_pages_that_use_the_preview_address_and_are_marked_view_only(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/pic.jpg', 'fake-image-bytes');

        // These paths are made NOW, before the export applies the preview address
        // (afterwards, generated paths would start with its folder name)
        $productPath = route('products.show', 'gold-mirror', false);
        $postPath    = route('blog.show', 'hello-world', false);
        $toolPath    = route('tools.show', 'paint-calculator', false);
        $legalPath   = route('legal.affiliate', [], false);

        $category = Category::create(['name' => 'Mirrors', 'slug' => 'mirrors', 'is_active' => true, 'sort_order' => 1]);
        $this->makeProduct(['name' => 'Gold Mirror', 'slug' => 'gold-mirror', 'image' => 'products/pic.jpg', 'category_id' => $category->id]);
        BlogPost::create([
            'user_id' => $this->makeAdmin()->id, 'title' => 'Hello World Post', 'slug' => 'hello-world', 'content' => 'Some content.',
            'status' => 'published', 'published_at' => now()->subDay(),
        ]);

        $out = $this->folder();
        $this->run_export($out)->assertExitCode(0);

        foreach (['/', '/shop', '/shop/mirrors', '/blog', '/about', '/contact', '/tools', '/helper', '/assistant', '/recommended',
                  $productPath, $postPath, $toolPath, $legalPath] as $path) {
            $this->assertFileExists($this->page($out, $path), $path);
        }
        foreach (['404.html', '.nojekyll', '.sgh-export', 'README.md', 'assets/css/style.css'] as $file) {
            $this->assertFileExists($out . '/' . $file, $file);
        }

        $home = file_get_contents($this->page($out, '/'));
        $this->assertStringContainsString('https://example.github.io/preview/shop', $home);
        $this->assertStringContainsString('https://example.github.io/preview/assets/css/style.css', $home);
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $home);
        $this->assertStringContainsString('Preview for the team', $home);
        $this->assertStringContainsString("addEventListener('submit'", $home);
        $this->assertStringNotContainsString('http://localhost', $home);

        $product = file_get_contents($this->page($out, $productPath));
        $this->assertStringContainsString('Gold Mirror', $product);
        $this->assertStringContainsString('https://example.github.io/preview/storage/products/pic.jpg', $product);
        $this->assertSame('fake-image-bytes', file_get_contents($out . '/storage/products/pic.jpg'));
    }

    public function test_nothing_private_or_unpublished_is_exported(): void
    {
        $draftProductPath = route('products.show', 'draft-lamp', false);
        $draftPostPath    = route('blog.show', 'unpublished-story', false);

        $category = Category::create(['name' => 'Secret Room', 'slug' => 'secret-room', 'is_active' => false, 'sort_order' => 1]);
        $this->makeProduct(['name' => 'Draft Lamp', 'slug' => 'draft-lamp', 'status' => 'draft']);
        $this->makeProduct(['name' => 'Hidden Vase', 'slug' => 'hidden-vase', 'category_id' => $category->id, 'status' => 'draft']);
        BlogPost::create([
            'user_id' => $this->makeAdmin()->id, 'title' => 'Unpublished Story', 'slug' => 'unpublished-story', 'content' => 'Private words.',
            'status' => 'draft', 'published_at' => null,
        ]);

        $out = $this->folder();
        $this->run_export($out)->assertExitCode(0);

        $this->assertFileDoesNotExist($this->page($out, $draftProductPath));
        $this->assertFileDoesNotExist($this->page($out, '/shop/secret-room'));
        $this->assertFileDoesNotExist($this->page($out, $draftPostPath));
        $this->assertDirectoryDoesNotExist($out . '/admin');
        $this->assertFileDoesNotExist($out . '/.env');

        foreach (File::allFiles($out) as $file) {
            $this->assertNotSame('.env', $file->getFilename());
            $this->assertStringNotContainsString('Unpublished Story', (string) @file_get_contents($file->getPathname()));
        }
    }

    public function test_it_refuses_unsafe_folders_and_a_bad_address(): void
    {
        // inside the project
        $this->run_export(base_path('some-export-folder'))->assertExitCode(1);
        $this->assertDirectoryDoesNotExist(base_path('some-export-folder'));

        // the project itself, and a folder that contains it
        $this->run_export(base_path())->assertExitCode(1);
        $this->run_export(dirname(base_path()))->assertExitCode(1);

        // a folder that has other things in it and was not made by this command
        $foreign = $this->folder();
        File::ensureDirectoryExists($foreign);
        file_put_contents($foreign . '/keep-me.txt', 'important');
        $this->run_export($foreign)->assertExitCode(1);
        $this->assertSame('important', file_get_contents($foreign . '/keep-me.txt'));

        // a bad address
        $this->run_export($this->folder(), ['--base' => 'http://example.com'])->assertExitCode(1);
        $this->run_export($this->folder(), ['--base' => 'not an address'])->assertExitCode(1);
    }

    public function test_running_it_again_replaces_the_old_files_but_keeps_the_git_history(): void
    {
        $out = $this->folder();
        $this->run_export($out)->assertExitCode(0);

        File::ensureDirectoryExists($out . '/.git');
        file_put_contents($out . '/.git/config', 'history');
        file_put_contents($out . '/old-page.html', 'stale');
        File::ensureDirectoryExists($out . '/old-folder');
        file_put_contents($out . '/old-folder/index.html', 'stale');

        $this->run_export($out)->assertExitCode(0);

        $this->assertSame('history', file_get_contents($out . '/.git/config'));
        $this->assertFileDoesNotExist($out . '/old-page.html');
        $this->assertDirectoryDoesNotExist($out . '/old-folder');
        $this->assertFileExists($this->page($out, '/'));
    }

    public function test_only_pictures_inside_the_public_disk_are_copied(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/ok.jpg', 'ok');

        $category = Category::create(['name' => 'Mirrors', 'slug' => 'mirrors', 'is_active' => true, 'sort_order' => 1, 'image' => 'categories/../../../secret.jpg']);
        $this->makeProduct(['slug' => 'ok-product', 'image' => 'products/ok.jpg', 'category_id' => $category->id]);

        $out = $this->folder();
        $this->run_export($out)->assertExitCode(0);

        $this->assertFileExists($out . '/storage/products/ok.jpg');
        $this->assertFileDoesNotExist($out . '/secret.jpg');
        $this->assertFileDoesNotExist(dirname($out) . '/secret.jpg');
    }
}
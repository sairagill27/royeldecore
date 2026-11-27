<?php

namespace App\Console\Commands;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

// Saves the public pages as plain HTML files, so they can be shown on GitHub Pages as a view-only preview.
// Your code, your .env and your database are never copied.
class SiteExport extends Command
{
    protected $signature = 'site:export
        {--base=https://sairagill27.github.io/royeldecore-preview : The address where the preview will live}
        {--out= : The folder for the files (default: a folder called royeldecore-preview next to the project)}';

    protected $description = 'Saves a view-only static copy of the public pages (for a GitHub Pages preview)';

    private array $storageFiles = [];
    private array $origins = [];
    private string $base = '';

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');

        if (! preg_match('#^https://[a-z0-9.-]+(/[A-Za-z0-9._~-]+)*$#i', $base)) {
            $this->error('The --base address must be a full https:// address, for example https://sairagill27.github.io/royeldecore-preview');

            return self::FAILURE;
        }

        $out = rtrim((string) ($this->option('out') ?: dirname(base_path()) . DIRECTORY_SEPARATOR . 'royeldecore-preview'), '\\/');

        if (($problem = $this->problemWith($out)) !== null) {
            $this->error($problem);

            return self::FAILURE;
        }

        $this->prepare($out);

        // Every link and picture in the pages will use the preview address, and nothing is saved while the pages are made
        // The list of pages is made FIRST, with plain paths. Once the preview address is forced, paths would start with its folder name.
        $this->base    = $base;
        $this->origins = array_values(array_unique(array_filter([rtrim((string) config('app.url'), '/'), 'http://localhost', 'https://localhost'])));

        $paths = $this->paths();

        config(['session.driver' => 'array', 'cache.default' => 'array', 'preview.enabled' => false, 'app.url' => $base]);
        URL::forceRootUrl($base);
        URL::forceScheme('https');

        $saved   = [];
        $skipped = [];

        foreach ($paths as $path) {
            $html = $this->render($path);

            if ($html === null) {
                $skipped[] = $path;
                continue;
            }

            $this->save($out, $path, $this->finish($html));
            $saved[] = $path;
        }

        // A friendly page for addresses that do not exist (GitHub Pages shows 404.html)
        $missing = $this->render('/page-that-does-not-exist-sgh', true);
        if ($missing !== null) {
            file_put_contents($out . '/404.html', $this->finish($missing));
        }

        $this->copyAssets($out);

        $this->info(count($saved) . ' pages saved in ' . $out);
        if ($skipped !== []) {
            $this->warn('Skipped (they did not answer normally): ' . implode(', ', $skipped));
        }
        $this->line('Next: open that folder, then commit and push it to the royeldecore-preview repository.');

        return self::SUCCESS;
    }

    // ---------- safety ----------

    // Never the project itself, a folder inside it, or a folder that contains it.
    // A folder that already has other things in it is only used if this command made it.
    private function problemWith(string $out): ?string
    {
        $norm = fn (string $p) => strtolower(rtrim(str_replace('\\', '/', $p), '/'));
        $a    = $norm((string) (realpath($out) ?: $out));
        $b    = $norm((string) realpath(base_path()));

        if ($a === $b || str_starts_with($a . '/', $b . '/') || str_starts_with($b . '/', $a . '/')) {
            return 'Please choose a folder outside the project, for example a folder next to it.';
        }

        if (is_dir($out)) {
            $items = array_diff(scandir($out) ?: [], ['.', '..', '.git']);

            if ($items !== [] && ! file_exists($out . '/.sgh-export')) {
                return 'That folder is not empty, and it was not made by this command. Please choose a new, empty folder.';
            }
        }

        return null;
    }

    // Empties the folder (but keeps its .git history) and marks it
    private function prepare(string $out): void
    {
        File::ensureDirectoryExists($out);

        foreach (File::directories($out) as $dir) {
            if (basename($dir) !== '.git') {
                File::deleteDirectory($dir);
            }
        }
        foreach (File::files($out, true) as $file) {
            File::delete($file->getPathname());
        }

        file_put_contents($out . '/.sgh-export', "Made by php artisan site:export. Do not put other files here.\n");
        file_put_contents($out . '/.nojekyll', '');   // tells GitHub Pages to show the files exactly as they are
        file_put_contents($out . '/README.md', "# RoyelDecore preview\n\nA view-only copy of the public pages, for review. It is made by a command and is not the real shop.\nForms, sign-in, the cart, the calculators and the chat are switched off.\n");
    }

    // ---------- which pages ----------

    private function paths(): array
    {
        $paths = ['/', '/shop', '/blog', '/about', '/contact', '/tools', '/helper', '/assistant', '/recommended'];

        foreach (['legal.privacy', 'legal.terms', 'legal.cookies', 'legal.disclaimer', 'legal.affiliate'] as $name) {
            if (Route::has($name)) {
                $paths[] = route($name, [], false);
            }
        }

        foreach ((array) config('tools', []) as $key => $tool) {
            if (! empty($tool['ready'])) {
                $paths[] = route('tools.show', $key, false);
            }
        }

        foreach (Category::where('is_active', true)->pluck('slug') as $slug) {
            $paths[] = '/shop/' . $slug;
        }
        foreach (Product::visible()->pluck('slug') as $slug) {
            $paths[] = route('products.show', $slug, false);
        }
        foreach (BlogCategory::pluck('slug') as $slug) {
            $paths[] = route('blog.category', $slug, false);
        }
        foreach (BlogPost::published()->pluck('slug') as $slug) {
            $paths[] = route('blog.show', $slug, false);
        }

        return array_values(array_unique($paths));
    }

    // ---------- making and saving the pages ----------

    private function render(string $path, bool $anyStatus = false): ?string
    {
        $kernel   = app(Kernel::class);
        $request  = Request::create($path, 'GET');
        $response = $kernel->handle($request);
        $status   = $response->getStatusCode();
        $html     = (string) $response->getContent();
        $kernel->terminate($request, $response);

        return ($status === 200 || $anyStatus) ? $html : null;
    }

    // Adds the "do not list this page" mark, a small badge, and a guard that switches forms off
    private function finish(string $html): string
    {
        // Links that still point at the address the pages were made from are pointed at the preview address
        foreach ($this->origins as $origin) {
            $html = str_replace([$origin, str_replace('/', '\/', $origin)], [$this->base, str_replace('/', '\/', $this->base)], $html);
        }

        $head = '<meta name="robots" content="noindex, nofollow">' . "\n";

        $tail = <<<'HTML'
<div style="position:fixed;left:12px;bottom:12px;z-index:2000;background:#ffc107;color:#2e3a2f;padding:6px 14px;border-radius:999px;font:600 13px/1.4 system-ui,sans-serif;box-shadow:0 4px 14px rgba(0,0,0,.25)">Preview for the team &middot; view only</div>
<script>
(function () {
    var say = function () { alert('This is a view-only preview. Forms, the cart, sign-in, the calculators and the chat are switched off.'); };
    document.addEventListener('submit', function (e) { e.preventDefault(); e.stopImmediatePropagation(); say(); }, true);
    var realFetch = window.fetch;
    window.fetch = function (url, options) {
        if (options && options.method && String(options.method).toUpperCase() !== 'GET') { return Promise.reject(new Error('preview')); }
        return realFetch.apply(this, arguments);
    };
})();
</script>

HTML;

        $html = $this->insertBefore($html, '</head>', $head);
        $html = $this->insertBefore($html, '</body>', $tail);

        // Remember which uploaded pictures this page uses, so they can be copied
        if (preg_match_all('#storage/[A-Za-z0-9_\-./]+\.(?:jpe?g|png|webp|gif)#i', $html, $found)) {
            foreach ($found[0] as $file) {
                $this->storageFiles[$file] = true;
            }
        }

        return $html;
    }

    private function insertBefore(string $html, string $marker, string $text): string
    {
        $pos = stripos($html, $marker);

        return $pos === false ? $html : substr($html, 0, $pos) . $text . substr($html, $pos);
    }

    // /shop becomes shop/index.html, so the address works without ".html"
    private function save(string $out, string $path, string $html): void
    {
        $clean  = trim((string) parse_url($path, PHP_URL_PATH), '/');
        $target = $out . '/' . ($clean === '' ? 'index.html' : $clean . '/index.html');

        File::ensureDirectoryExists(dirname($target));
        file_put_contents($target, $html);
    }

    // ---------- copying files ----------

    private function copyAssets(string $out): void
    {
        foreach (['assets', 'build'] as $folder) {
            if (is_dir(public_path($folder))) {
                File::copyDirectory(public_path($folder), $out . '/' . $folder);
            }
        }

        foreach (['favicon.ico', 'favicon.svg', 'favicon.png', 'apple-touch-icon.png'] as $file) {
            if (is_file(public_path($file))) {
                copy(public_path($file), $out . '/' . $file);
            }
        }

        // Pictures uploaded in the admin panel: only the ones the pages really use, and only from the public disk
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));

        foreach (array_keys($this->storageFiles) as $file) {
            $relative = substr($file, strlen('storage/'));
            $source   = realpath($disk->path($relative));

            if ($root && $source && str_starts_with($source, $root . DIRECTORY_SEPARATOR) && is_file($source)) {
                File::ensureDirectoryExists(dirname($out . '/' . $file));
                copy($source, $out . '/' . $file);
            }
        }
    }
}
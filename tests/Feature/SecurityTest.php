<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // Every admin address, with the {parts} replaced by a number. Each has the method it answers to.
    private function adminRoutes(): array
    {
        $found = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'admin')) {
                continue;
            }

            $method = collect($route->methods())->first(fn ($m) => ! in_array($m, ['HEAD', 'OPTIONS'], true));
            $found[] = [$method, '/' . preg_replace('/\{[^}]+\}/', '1', $route->uri())];
        }

        return $found;
    }

    // ---------- the admin area ----------

    public function test_every_admin_page_and_action_sends_visitors_to_the_login_page(): void
    {
        $routes = $this->adminRoutes();
        $this->assertGreaterThan(30, count($routes), 'The route list looks too short.');

        foreach ($routes as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect(route('login'));
        }
    }

    public function test_every_admin_page_and_action_refuses_customers(): void
    {
        // Rate limits share one counter per user, and this test visits every admin address in a row.
        // Limits have their own tests, so they are switched off here.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->actingAs($this->makeCustomer());

        foreach ($this->adminRoutes() as [$method, $uri]) {
            $status = $this->call($method, $uri)->getStatusCode();
            $this->assertContains($status, [403, 404], $method . ' ' . $uri . ' answered ' . $status . ' for a customer');
        }
    }

    // ---------- forms cannot set sensitive columns ----------

    public function test_sensitive_columns_cannot_be_set_from_a_form(): void
    {
        $this->assertFalse((new User())->isFillable('role'));
        $this->assertFalse((new User())->isFillable('is_active'));

        foreach ([new Comment(), new ProductReview()] as $model) {
            $this->assertFalse($model->isFillable('status'));
            $this->assertFalse($model->isFillable('user_id'));
        }
    }

    // ---------- the public folder ----------

    public function test_no_secret_backup_or_stray_script_sits_in_the_public_folder(): void
    {
        $files = collect(File::files(public_path()))
            ->merge(File::allFiles(public_path('assets')));

        $forbidden = ['env', 'sql', 'bak', 'zip', 'gz', 'tar', 'log', 'old', 'orig', 'swp'];

        foreach ($files as $file) {
            $this->assertNotContains(strtolower($file->getExtension()), $forbidden, $file->getFilename() . ' should not be public.');
        }

        // The only script people may open directly is index.php
        $scripts = collect(File::files(public_path()))
            ->filter(fn ($f) => strtolower($f->getExtension()) === 'php')
            ->map(fn ($f) => $f->getFilename())
            ->values()
            ->all();
        $this->assertSame(['index.php'], $scripts);

        $this->assertSame(
            [],
            collect(File::allFiles(public_path('assets')))->filter(fn ($f) => strtolower($f->getExtension()) === 'php')->all()
        );
    }

    // ---------- search engines ----------

    public function test_the_sitemap_never_lists_private_pages(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/admin', '/cart', '/checkout', '/account', '/login', '/register', '/wishlist', '/password', '/reset-password'] as $private) {
            $this->assertStringNotContainsString($private, $xml, $private . ' must not be in the sitemap.');
        }
    }

    // ---------- developer tools ----------

    public function test_developer_only_pages_do_not_exist_outside_the_local_environment(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertStringStartsNotWith('dev/', $route->uri());
        }
    }

    // ---------- cookies and headers ----------

    public function test_session_cookies_cannot_be_read_by_scripts_and_are_not_sent_across_sites(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
    }

    public function test_pages_tell_browsers_not_to_guess_file_types(): void
    {
        $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
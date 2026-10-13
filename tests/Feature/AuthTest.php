<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // Every page in the admin area
    private const ADMIN_URLS = [
        '/admin',
        '/admin/products',
        '/admin/categories',
        '/admin/orders',
        '/admin/customers',
        '/admin/messages',
    ];

    public function test_a_visitor_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sara Test',
            'email' => 'Sara@Example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ]);

        $response->assertRedirect(route('account'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'sara@example.com', 'role' => 'customer']);
        // The password is saved scrambled, never as typed
        $this->assertNotSame('Secret123', User::first()->password);
    }

    public function test_nobody_can_make_themselves_admin(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'sneaky@example.com', 'role' => 'customer']);
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->post('/register', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_a_customer_can_log_in(): void
    {
        $user = $this->makeCustomer();   // the factory password is "password"

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $user = $this->makeCustomer();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-one'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_blocked_customer_cannot_log_in(): void
    {
        $user = $this->makeCustomer(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_locks_after_five_wrong_attempts(): void
    {
        $user = $this->makeCustomer();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-one']);
        }

        // Even the CORRECT password is refused while locked
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }

    public function test_visitors_are_sent_to_the_login_page(): void
    {
        foreach (['/admin', '/account', '/checkout', '/account/orders'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_a_customer_can_never_open_the_admin_area(): void
    {
        $customer = $this->makeCustomer();

        foreach (self::ADMIN_URLS as $url) {
            $this->actingAs($customer)->get($url)->assertForbidden();
        }
    }

    public function test_a_blocked_admin_is_locked_out_too(): void
    {
        $this->actingAs($this->makeAdmin(['is_active' => false]))->get('/admin')->assertForbidden();
    }

    public function test_staff_and_admin_can_open_the_admin_area(): void
    {
        foreach ([$this->makeAdmin(), $this->makeStaff()] as $user) {
            foreach (self::ADMIN_URLS as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
    }
}

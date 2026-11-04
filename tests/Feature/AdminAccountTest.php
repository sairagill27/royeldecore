<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'Old-Password-9!';

    // An admin whose password we know
    private function adminWithPassword()
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make(self::OLD)])->save();

        return $admin;
    }

    // A login on some OTHER device, as the database session driver would save it
    private function otherDevice($user): void
    {
        DB::table('sessions')->insert([
            'id'            => 'other-device-session',
            'user_id'       => $user->id,
            'ip_address'    => '10.0.0.9',
            'user_agent'    => 'phone',
            'payload'       => 'x',
            'last_activity' => time(),
        ]);
    }

    private function newPassword(array $o = []): array
    {
        return array_merge([
            'current_password'      => self::OLD,
            'password'              => 'correct horse battery staple',
            'password_confirmation' => 'correct horse battery staple',
        ], $o);
    }

    // ---------- who can open it ----------

    public function test_staff_and_admin_can_open_the_page_but_customers_and_visitors_cannot(): void
    {
        foreach ([$this->makeAdmin(), $this->makeStaff()] as $user) {
            $this->actingAs($user)->get(route('admin.account.edit'))->assertOk()
                ->assertSee('My account')
                ->assertSee('Change your password');
        }

        $this->actingAs($this->makeCustomer())->get(route('admin.account.edit'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.account.edit'))->assertRedirect(route('login'));
    }

    public function test_the_page_is_behind_the_two_step_check_when_it_is_on(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill([
            'two_factor_secret'       => Crypt::encryptString('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($admin)->get(route('admin.account.edit'))->assertRedirect(route('admin.two-factor.challenge'));
    }

    // ---------- name and email ----------

    public function test_the_name_can_change_without_the_password(): void
    {
        $admin = $this->adminWithPassword();

        $this->actingAs($admin)->patch(route('admin.account.update'), ['name' => 'Royel Owner', 'email' => $admin->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('Royel Owner', $admin->fresh()->name);
    }

    public function test_changing_the_email_needs_the_current_password_and_logs_out_other_devices(): void
    {
        $admin = $this->adminWithPassword();
        $this->otherDevice($admin);
        $oldEmail = $admin->email;

        // No password
        $this->actingAs($admin)->patch(route('admin.account.update'), ['name' => $admin->name, 'email' => 'owner@example.com'])
            ->assertSessionHasErrors('email_password');

        // Wrong password
        $this->actingAs($admin)->patch(route('admin.account.update'), ['name' => $admin->name, 'email' => 'owner@example.com', 'email_password' => 'wrong-password'])
            ->assertSessionHasErrors('email_password');

        $this->assertSame($oldEmail, $admin->fresh()->email);
        $this->assertDatabaseHas('sessions', ['id' => 'other-device-session']);

        // Right password: saved in lowercase, other devices logged out
        $this->actingAs($admin)->patch(route('admin.account.update'), ['name' => $admin->name, 'email' => 'Shop.Owner@Example.com', 'email_password' => self::OLD])
            ->assertSessionHasNoErrors();

        $this->assertSame('shop.owner@example.com', $admin->fresh()->email);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    }

    public function test_an_email_that_is_taken_or_not_valid_is_refused(): void
    {
        $admin = $this->adminWithPassword();
        $other = $this->makeStaff();

        foreach (['not-an-email', $other->email, ['x@example.com'], ''] as $bad) {
            $this->actingAs($admin)->patch(route('admin.account.update'), ['name' => $admin->name, 'email' => $bad, 'email_password' => self::OLD])
                ->assertSessionHasErrors('email');
        }

        $this->assertNotSame($other->email, $admin->fresh()->email);
    }

    public function test_the_role_cannot_be_changed_from_the_form(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->patch(route('admin.account.update'), ['name' => 'Sneaky', 'email' => $staff->email, 'role' => 'admin'])
            ->assertSessionHasNoErrors();

        $this->assertSame('staff', $staff->fresh()->role);
    }

    public function test_only_your_own_account_changes(): void
    {
        $staff = $this->makeStaff();
        $admin = $this->makeAdmin();
        $before = $admin->name;

        $this->actingAs($staff)->patch(route('admin.account.update'), ['name' => 'Changed Name', 'email' => $staff->email, 'id' => $admin->id]);

        $this->assertSame('Changed Name', $staff->fresh()->name);
        $this->assertSame($before, $admin->fresh()->name);
    }

    // ---------- password ----------

    public function test_the_password_can_be_changed_and_other_devices_are_logged_out(): void
    {
        $admin = $this->adminWithPassword();
        $this->otherDevice($admin);

        $this->actingAs($admin)->put(route('admin.account.password'), $this->newPassword())
            ->assertSessionHasNoErrors();

        $saved = $admin->fresh();
        $this->assertTrue(Hash::check('correct horse battery staple', $saved->password));
        $this->assertFalse(Hash::check(self::OLD, $saved->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    }

    public function test_bad_new_passwords_are_refused(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);   // several requests in a row

        $admin = $this->adminWithPassword();

        $cases = [
            ['current_password' => 'wrong-password'],                                            // wrong current password
            ['password' => 'short-pass-1', 'password_confirmation' => 'short-pass-1'],           // shorter than 14
            ['password' => str_repeat('a', 13), 'password_confirmation' => str_repeat('a', 13)], // 13 characters
            ['password_confirmation' => 'something else entirely'],                              // the two do not match
            ['password' => self::OLD, 'password_confirmation' => self::OLD],                     // same as the current one
        ];

        foreach ($cases as $change) {
            $this->actingAs($admin)->put(route('admin.account.password'), $this->newPassword($change))
                ->assertSessionHasErrors(array_key_first($change) === 'password_confirmation' ? 'password' : array_key_first($change));
        }

        $this->assertTrue(Hash::check(self::OLD, $admin->fresh()->password));
    }

    // ---------- the sidebar ----------

    public function test_the_sidebar_links_to_the_page(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('My account')
            ->assertSee(route('admin.account.edit'), false);
    }
}
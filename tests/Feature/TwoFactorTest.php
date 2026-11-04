<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    // The official test key from RFC 6238 (the text "12345678901234567890", written in base32)
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    private function currentCode(string $secret, int $shift = 0): string
    {
        return TwoFactor::code($secret, intdiv(now()->timestamp, 30) + $shift);
    }

    // A code that is certainly wrong right now
    private function wrongCode(string $secret): string
    {
        $valid = [$this->currentCode($secret, -1), $this->currentCode($secret), $this->currentCode($secret, 1)];

        return in_array('000000', $valid, true) ? '111111' : '000000';
    }

    // Turns two-step login on for a user straight in the database. Returns [key, recovery codes].
    private function turnOn(User $user): array
    {
        $secret = TwoFactor::generateSecret();
        $codes  = ['abcde-12345', 'fghij-67890'];

        $user->forceFill([
            'two_factor_secret'         => Crypt::encryptString($secret),
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => TwoFactor::hashCodes($codes),
            'two_factor_last_step'      => 0,
        ])->save();

        return [$secret, $codes];
    }

    // ---------- the maths ----------

    public function test_codes_match_the_official_test_values(): void
    {
        $this->assertSame('287082', TwoFactor::code(self::RFC_SECRET, 1));
        $this->assertSame('081804', TwoFactor::code(self::RFC_SECRET, 37037036));
        $this->assertSame('050471', TwoFactor::code(self::RFC_SECRET, 37037037));
        $this->assertSame('005924', TwoFactor::code(self::RFC_SECRET, 41152263));
        $this->assertSame('279037', TwoFactor::code(self::RFC_SECRET, 66666666));
        $this->assertSame('353130', TwoFactor::code(self::RFC_SECRET, 666666666));
    }

    public function test_secrets_are_random_base32_and_round_trip(): void
    {
        $a = TwoFactor::generateSecret();
        $b = TwoFactor::generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        $this->assertNotSame($a, $b);

        $this->assertSame(self::RFC_SECRET, TwoFactor::base32Encode('12345678901234567890'));
        $this->assertSame('12345678901234567890', TwoFactor::base32Decode(self::RFC_SECRET));
        $this->assertSame('12345678901234567890', TwoFactor::base32Decode(strtolower(self::RFC_SECRET)));
        $this->assertSame('hello', TwoFactor::base32Decode(TwoFactor::base32Encode('hello')));
    }

    public function test_neighbouring_30_second_codes_are_accepted_but_older_and_malformed_ones_are_not(): void
    {
        $now    = 1000000000;
        $step   = intdiv($now, 30);
        $secret = self::RFC_SECRET;

        $this->assertSame($step - 1, TwoFactor::verify($secret, TwoFactor::code($secret, $step - 1), $now));
        $this->assertSame($step, TwoFactor::verify($secret, TwoFactor::code($secret, $step), $now));
        $this->assertSame($step + 1, TwoFactor::verify($secret, TwoFactor::code($secret, $step + 1), $now));

        $this->assertNull(TwoFactor::verify($secret, TwoFactor::code($secret, $step - 2), $now));
        $this->assertNull(TwoFactor::verify($secret, TwoFactor::code($secret, $step + 2), $now));

        foreach (['abcdef', '12345', '1234567', '', ' '] as $bad) {
            $this->assertNull(TwoFactor::verify($secret, $bad, $now));
        }

        $code = TwoFactor::code($secret, $step);   // a space in the middle is fine: "123 456"
        $this->assertSame($step, TwoFactor::verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3), $now));
    }

    // ---------- turning it on ----------

    public function test_staff_can_turn_it_on_with_a_code_from_their_app(): void
    {
        $admin = $this->makeAdmin();

        $page = $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertOk()
            ->assertSee('Turn on two-step login')
            ->assertSee('otpauth://totp/', false);
        $this->assertStringContainsString('no-store', $page->headers->get('Cache-Control'));

        $secret = session('two_factor_pending_secret');
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);

        $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertSee(trim(chunk_split($secret, 4, ' ')));

        // A wrong code does not turn it on
        $this->actingAs($admin)->post(route('admin.two-factor.enable'), ['code' => $this->wrongCode($secret)])
            ->assertSessionHasErrors('code');
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);

        // The right code does
        $this->actingAs($admin)->post(route('admin.two-factor.enable'), ['code' => $this->currentCode($secret)])
            ->assertRedirect(route('admin.two-factor.setup'))
            ->assertSessionHas('recovery_codes');

        $saved = $admin->fresh();
        $this->assertCount(8, session('recovery_codes'));
        $this->assertNotNull($saved->two_factor_confirmed_at);
        $this->assertNotSame($secret, $saved->two_factor_secret);                       // saved encrypted
        $this->assertSame($secret, Crypt::decryptString($saved->two_factor_secret));
        $this->assertCount(8, json_decode($saved->two_factor_recovery_codes, true));
        $this->assertStringNotContainsString(session('recovery_codes')[0], $saved->two_factor_recovery_codes);   // hashes only
    }

    public function test_recovery_codes_are_shown_once_and_the_secret_never_again(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.two-factor.setup'));
        $secret = session('two_factor_pending_secret');
        $this->actingAs($admin)->post(route('admin.two-factor.enable'), ['code' => $this->currentCode($secret)]);
        $codes = session('recovery_codes');

        $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertOk()
            ->assertSee('Two-step login is on')
            ->assertSee($codes[0])
            ->assertDontSee($secret);

        $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertOk()
            ->assertDontSee($codes[0])
            ->assertDontSee($secret)
            ->assertSee('You have 8 recovery codes left');
    }

    // ---------- logging in ----------

    public function test_with_two_step_on_the_admin_area_asks_for_a_code(): void
    {
        $admin = $this->makeAdmin();
        $this->turnOn($admin);

        $challenge = route('admin.two-factor.challenge');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect($challenge);
        $this->actingAs($admin)->get(route('admin.posts.index'))->assertRedirect($challenge);
        $this->actingAs($admin)->post(route('admin.posts.store'), ['title' => 'x'])->assertRedirect($challenge);

        $this->actingAs($admin)->get($challenge)->assertOk()
            ->assertSee('Two-step login')
            ->assertSee('recovery code');
    }

    public function test_a_right_code_lets_you_in_and_returns_you_to_the_page_you_wanted(): void
    {
        $admin = $this->makeAdmin();
        [$secret] = $this->turnOn($admin);

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertRedirect(route('admin.two-factor.challenge'));

        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => $this->currentCode($secret)])
            ->assertRedirect(route('admin.orders.index'));

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();
    }

    public function test_the_same_code_cannot_be_used_twice(): void
    {
        $admin = $this->makeAdmin();
        [$secret] = $this->turnOn($admin);
        $code = $this->currentCode($secret);

        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => $code])
            ->assertSessionHasNoErrors();

        $this->flushSession();   // a new login

        $this->actingAs($admin->fresh())->post(route('admin.two-factor.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_five_wrong_codes_lock_it_for_a_while(): void
    {
        $admin = $this->makeAdmin();
        [$secret] = $this->turnOn($admin);
        $wrong = $this->wrongCode($secret);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => $wrong])
                ->assertSessionHasErrors('code');
        }

        // Even the right code is refused now
        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => $this->currentCode($secret)])
            ->assertSessionHasErrors('code');
        $this->assertStringContainsString('Too many', session('errors')->first('code'));

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_a_recovery_code_works_once(): void
    {
        $admin = $this->makeAdmin();
        [, $codes] = $this->turnOn($admin);

        // Capital letters and no dash are fine
        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => strtoupper(str_replace('-', '', $codes[0]))])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertCount(1, json_decode($admin->fresh()->two_factor_recovery_codes, true));

        $this->flushSession();

        $this->actingAs($admin->fresh())->post(route('admin.two-factor.verify'), ['code' => $codes[0]])
            ->assertSessionHasErrors('code');
    }

    // ---------- turning it off and making new codes ----------

    public function test_turning_it_off_needs_the_password_and_a_code(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make('Correct-Horse-9!')])->save();
        [$secret] = $this->turnOn($admin);
        $passed = ['two_factor_ok' => $admin->id];

        $this->withSession($passed)->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['password' => 'wrong-password', 'code' => $this->currentCode($secret)])
            ->assertSessionHasErrors('disable');

        $this->withSession($passed)->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['password' => 'Correct-Horse-9!', 'code' => $this->wrongCode($secret)])
            ->assertSessionHasErrors('disable');

        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);

        $this->withSession($passed)->actingAs($admin)
            ->post(route('admin.two-factor.disable'), ['password' => 'Correct-Horse-9!', 'code' => $this->currentCode($secret)])
            ->assertSessionHasNoErrors();

        $off = $admin->fresh();
        $this->assertNull($off->two_factor_confirmed_at);
        $this->assertNull($off->two_factor_secret);
        $this->assertNull($off->two_factor_recovery_codes);
    }

    public function test_new_recovery_codes_need_the_password(): void
    {
        $admin = $this->makeAdmin();
        $admin->forceFill(['password' => Hash::make('Correct-Horse-9!')])->save();
        $this->turnOn($admin);
        $before = $admin->fresh()->two_factor_recovery_codes;
        $passed = ['two_factor_ok' => $admin->id];

        $this->withSession($passed)->actingAs($admin)
            ->post(route('admin.two-factor.recovery'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('recovery');
        $this->assertSame($before, $admin->fresh()->two_factor_recovery_codes);

        $this->withSession($passed)->actingAs($admin)
            ->post(route('admin.two-factor.recovery'), ['password' => 'Correct-Horse-9!'])
            ->assertSessionHas('recovery_codes');

        $this->assertCount(8, session('recovery_codes'));
        $this->assertNotSame($before, $admin->fresh()->two_factor_recovery_codes);
    }

    // ---------- who can use it ----------

    public function test_the_code_page_is_staff_only_and_skipped_without_two_step(): void
    {
        $this->actingAs($this->makeCustomer())->get(route('admin.two-factor.challenge'))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.two-factor.challenge'))->assertRedirect(route('login'));

        $this->actingAs($this->makeAdmin())->get(route('admin.two-factor.challenge'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_when_required_an_admin_without_two_step_is_sent_to_set_it_up(): void
    {
        config(['security.two_factor_required' => true]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('admin.two-factor.setup'));
        $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertOk()->assertSee('requires two-step login');
        $this->actingAs($this->makeStaff())->get(route('admin.orders.index'))->assertRedirect(route('admin.two-factor.setup'));
        $this->actingAs($this->makeCustomer())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_an_unreadable_secret_does_not_lock_out_someone_with_a_recovery_code(): void
    {
        $admin = $this->makeAdmin();
        [, $codes] = $this->turnOn($admin);
        $admin->forceFill(['two_factor_secret' => 'not-a-valid-encrypted-value'])->save();

        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->actingAs($admin)->post(route('admin.two-factor.verify'), ['code' => $codes[1]])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_reset_command_turns_it_off_for_one_account(): void
    {
        $admin = $this->makeAdmin();
        $this->turnOn($admin);

        $this->artisan('two-factor:reset', ['email' => 'nobody@example.com'])->assertExitCode(1);
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);

        $this->artisan('two-factor:reset', ['email' => $admin->email])->assertExitCode(0);
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
        $this->assertNull($admin->fresh()->two_factor_secret);
    }

    public function test_the_menu_links_to_the_page(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Two-step login')
            ->assertSee(route('admin.two-factor.setup'), false);
    }
}
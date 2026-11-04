<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

// One-time codes, as defined in the public standard RFC 6238 (the kind authenticator apps use).
class TwoFactor
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD   = 30;   // a new code every 30 seconds

    // Is two-step login switched on for this user?
    public static function isOn($user): bool
    {
        return $user->two_factor_confirmed_at !== null && filled($user->two_factor_secret);
    }

    // A new random key: 20 random bytes written as 32 letters and digits
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text));

        $bits = '';
        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    // The 6-digit code for one 30-second step
    public static function code(string $secret, int $step): string
    {
        $key  = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N*', 0, $step), $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $number = ((ord($hash[$offset]) & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                |  (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($number % 1000000), 6, '0', STR_PAD_LEFT);
    }

    // Checks a typed code. Accepts the step before and after now (clocks are never exact).
    // Returns the step the code belongs to, or null if it is wrong.
    public static function verify(string $secret, string $code, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $step = intdiv($now ?? now()->timestamp, self::PERIOD);

        for ($shift = -1; $shift <= 1; $shift++) {
            if (hash_equals(self::code($secret, $step + $shift), $code)) {
                return $step + $shift;
            }
        }

        return null;
    }

    // The text an authenticator app reads to add the account (a QR code holds the same text)
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=' . self::PERIOD;
    }

    // Eight one-time recovery codes like "3fa91-0c7d2"
    public static function newRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $hex     = bin2hex(random_bytes(5));   // 10 characters
            $codes[] = substr($hex, 0, 5) . '-' . substr($hex, 5);
        }

        return $codes;
    }

    // Only hashes are saved, so a copy of the database does not reveal the codes
    public static function hashCodes(array $codes): string
    {
        return json_encode(array_map(fn ($code) => Hash::make($code), $codes));
    }

    // "ABCDE12345" and "abcde-12345" are the same code
    public static function normalizeRecovery(string $input): string
    {
        $clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $input));

        return strlen($clean) === 10 ? substr($clean, 0, 5) . '-' . substr($clean, 5) : $clean;
    }

    // If the code matches, returns the hashes that are left (this one is used up). Otherwise null.
    public static function consumeRecovery(?string $stored, string $input): ?array
    {
        $hashes = json_decode((string) $stored, true);

        if (! is_array($hashes)) {
            return null;
        }

        $code = self::normalizeRecovery($input);

        foreach ($hashes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashes[$index]);

                return array_values($hashes);
            }
        }

        return null;
    }
}
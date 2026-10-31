<?php

namespace App\Services;

use App\Mail\AdminAlertMail;
use Illuminate\Support\Facades\Mail;

class AdminAlert
{
    // Tells the shop owner that something is waiting.
    // It does nothing if no valid owner email is set, and a mail problem NEVER stops the visitor's action.
    public static function send(string $subject, string $headline, array $lines, ?string $url = null, string $button = 'Open the admin panel'): void
    {
        $to = config('shop.admin_email');

        if (! is_string($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($to)->send(new AdminAlertMail($subject, $headline, $lines, $url, $button));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
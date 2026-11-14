<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Str;

class Audit
{
    // Writes one line in the activity log: who did what. A problem here NEVER stops the action itself.
    // Never put passwords, keys or affiliate addresses in the text.
    public static function record(string $action, ?string $subject = null, ?string $details = null): void
    {
        try {
            $user = request()->user();

            AuditLog::create([
                'user_id' => $user?->id,
                'action'  => Str::limit($action, 60, ''),
                'subject' => $subject !== null ? Str::limit($subject, 120, '') : null,
                'details' => $details !== null ? Str::limit($details, 1000, '') : null,
                'ip'      => $user ? request()->ip() : null,   // only staff are recorded with an address
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
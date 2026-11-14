<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One line in the activity log. Entries are only ever added, never edited.
class AuditLog extends Model
{
    public $timestamps = false;   // created_at is filled in by the database

    protected $fillable = ['user_id', 'action', 'subject', 'details', 'ip'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
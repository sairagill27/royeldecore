<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    // read_at is NOT here on purpose: only the admin inbox changes it
    protected $fillable = ['name', 'email', 'subject', 'message'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}

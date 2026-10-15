<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    // unsubscribed_at is NOT here on purpose: only the unsubscribe code changes it
    protected $fillable = ['email', 'token'];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    // status, blog_post_id and user_id are NOT here on purpose:
    // the controller sets them, so a visitor can never choose them.
    protected $fillable = ['name', 'email', 'body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Comments visitors may see
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
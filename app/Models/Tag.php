<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    // One tag belongs to many posts
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'post_tag', 'tag_id', 'blog_post_id');
    }
}

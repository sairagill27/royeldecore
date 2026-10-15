<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    // Only the admin forms save posts, and they check every value first
    protected $fillable = [
        'user_id',
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'featured_image_alt',
        'status',
        'published_at',
        'seo_title',
        'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // The author
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'blog_post_id', 'tag_id');
    }

    // Posts visitors may read: published, and the publish date has arrived.
    // Use it like: BlogPost::published()->latest('published_at')->get()
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    // "5 min read": about 200 words a minute, at least 1 minute
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(Str::wordCount(strip_tags($this->content)) / 200));
    }
}

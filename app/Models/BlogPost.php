<?php

namespace App\Models;

use App\Services\Markdown;
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

    // For lists and cards: loads only what a card shows. The whole article text is
    // NOT loaded (only its first 400 characters, used if there is no summary).
    // Use it like: BlogPost::published()->forCards()->get()
    public function scopeForCards(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'user_id',
                'blog_category_id',
                'title',
                'slug',
                'excerpt',
                'featured_image',
                'featured_image_alt',
                'published_at'
            ])
            ->selectRaw('SUBSTR(content, 1, 400) as content_start')
            ->with(['category:id,name,slug', 'author:id,name']);
    }

    // The short text on a card: your summary, or the start of the article
    public function summary(int $limit = 160): string
    {
        if (filled($this->excerpt)) {
            return Str::limit($this->excerpt, $limit);
        }

        $text  = $this->attributes['content'] ?? $this->attributes['content_start'] ?? '';
        $plain = html_entity_decode(strip_tags(Markdown::toHtml($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(Str::squish($plain), $limit);
    }

    // "5 min read": about 200 words a minute, at least 1 minute
    public function readingMinutes(): int
    {
        return max(1, (int) ceil(Str::wordCount(strip_tags($this->content)) / 200));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateLink extends Model
{
    public const STATUSES = ['active' => 'Active', 'paused' => 'Paused', 'expired' => 'Expired'];

    // Only the admin forms save links, and they check every value first.
    // The click counter and the "last checked" date are never mass-assigned.
    protected $fillable = [
        'name', 'merchant', 'slug', 'url', 'description', 'section', 'image',
        'status', 'expires_at', 'is_featured', 'is_direct', 'blog_category_id',
    ];

    protected $casts = [
        'is_direct'       => 'boolean',
        'is_featured'     => 'boolean',
        'expires_at'      => 'date',
        'last_checked_at' => 'datetime',
    ];

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    private static function today(): string
    {
        return now(config('shop.timezone'))->toDateString();
    }

    // Links visitors may see: status Active, and not past their end date (the end date itself still counts)
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', self::today()));
    }

    // Filter by what the admin sees. An active link past its end date counts as expired.
    public function scopeInState(Builder $query, string $state): Builder
    {
        return match ($state) {
            'active'  => $query->active(),
            'paused'  => $query->where('status', 'paused'),
            'expired' => $query->where(fn ($q) => $q->where('status', 'expired')
                ->orWhere(fn ($late) => $late->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<', self::today()))),
            default   => $query,
        };
    }

    // active, paused or expired, as visitors experience it
    public function state(): string
    {
        if ($this->status === 'active' && $this->expires_at && $this->expires_at->toDateString() < self::today()) {
            return 'expired';
        }

        return array_key_exists((string) $this->status, self::STATUSES) ? $this->status : 'paused';
    }

    // An active link that no person has opened and checked lately
    public function needsCheck(): bool
    {
        return $this->state() === 'active'
            && (! $this->last_checked_at || $this->last_checked_at->lt(now()->subDays((int) config('affiliate.recheck_days', 30))));
    }

    // Where the buttons point: the short address on this site (which counts the click),
    // or straight to the store when the link is marked "direct"
    public function href(): string
    {
        return $this->is_direct ? $this->url : route('affiliate.go', $this->slug);
    }

    // Is this address safe to send visitors to? It must be a full https:// address of ANOTHER website.
    // Everything else (javascript:, http://, numbers like 127.0.0.1, passwords in the address, this site itself) is refused.
    public static function safeUrl(mixed $url): bool
    {
        if (! is_string($url) || $url === '' || strlen($url) > 2000 || preg_match('/[\s\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        // A real web address: letters, digits, dots and dashes, with at least one dot, and not a number address
        if (! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/', $host)
            || filter_var($host, FILTER_VALIDATE_IP)
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.localhost')) {
            return false;
        }

        // It must lead to another store, never back to this website
        return $host !== strtolower((string) parse_url(url('/'), PHP_URL_HOST));
    }
}
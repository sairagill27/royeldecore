<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'image',
        'status',
        'is_featured',
        'seo_title',
        'meta_description',
    ];

    protected $casts = [
        'price'       => 'decimal:2',   // always 2 decimals, e.g. 1500.00
        'is_featured' => 'boolean',
    ];

    // RELATIONSHIP: each product belongs to one category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Products customers may see: active, and not inside a hidden category.
    // Use it like: Product::visible()->get()
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $q) {
                $q->whereNull('category_id')
                    ->orWhereHas('category', fn(Builder $c) => $c->where('is_active', true));
            });
    }
}

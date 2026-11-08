<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{

    // The extra photos of this product (the main photo stays in the "image" column)
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    // When a product is deleted, its gallery photos are removed from the disk too
    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            foreach ($product->images as $photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($photo->path);
            }
        });
    }
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

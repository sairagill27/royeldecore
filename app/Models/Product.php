<?php

namespace App\Models;

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
}

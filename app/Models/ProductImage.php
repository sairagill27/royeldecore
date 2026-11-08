<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    // Set by the admin controller, never taken from a form
    protected $fillable = ['product_id', 'path', 'alt', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
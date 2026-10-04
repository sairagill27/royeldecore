<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // Fields allowed to be saved from forms (protects against mass-assignment)
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'sort_order',
    ];

    // is_active comes from the database as 0/1; this turns it into true/false
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // RELATIONSHIP: one category has many products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

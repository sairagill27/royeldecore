<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class WishlistItem extends Model
{
    // Set by the controller from the logged-in user, never taken from a form
    protected $fillable = ['user_id', 'product_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // The ids of the products this customer has saved. Looked up ONCE per page request,
    // so a page with 40 product cards makes one query, not 40.
    public static function savedIds(?int $userId): Collection
    {
        if (! $userId) {
            return collect();
        }

        $request = request();
        $key     = 'wishlist_ids_' . $userId;

        if (! $request->attributes->has($key)) {
            $request->attributes->set($key, static::where('user_id', $userId)->pluck('product_id'));
        }

        return $request->attributes->get($key);
    }
}
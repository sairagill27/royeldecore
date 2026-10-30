<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProductReview extends Model
{
    // The order status that means "the customer has received it"
    public const DELIVERED = 'delivered';

    // status, product_id and user_id are NOT here on purpose:
    // the controller sets them, so a customer can never choose them.
    protected $fillable = ['rating', 'title', 'body'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Reviews visitors may see
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    // Has this customer RECEIVED this product? (a delivered order that contains it)
    public static function canBeWrittenBy(?User $user, int $productId): bool
    {
        if (! $user) {
            return false;
        }

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', self::DELIVERED)
            ->where('order_items.product_id', $productId)
            ->exists();
    }
}
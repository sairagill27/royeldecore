<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    // Order statuses from your roadmap (key = saved value, value = shown text)
    public const STATUSES = [
        'pending'    => 'Pending',
        'confirmed'  => 'Confirmed',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
        'refunded'   => 'Refunded',
    ];

    // status and payment_status are NOT here on purpose: they use database
    // defaults and are changed only by the admin later.
    protected $fillable = [
        'user_id',
        'order_number',
        'subtotal',
        'shipping_fee',
        'total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country',
        'notes',
        'payment_method',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Text shown to the customer, e.g. "Pending"
    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    // CSS class for the coloured badge, e.g. "sgh-status-pending"
    public function statusClass(): string
    {
        return 'sgh-status-' . $this->status;
    }
}

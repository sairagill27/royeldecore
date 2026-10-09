<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    // Moves an order to a new status. Everything happens in ONE database
    // transaction: if anything fails, nothing is changed.
    public function change(Order $order, string $newStatus, int $adminId): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $adminId) {

            // Lock the order while we work, so two admins cannot change it at the same moment
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from  = $order->status;

            if (! in_array($newStatus, $order->nextStatuses(), true)) {
                throw new CheckoutException(
                    'This order cannot move from ' . $order->statusLabel() . ' to ' . (Order::STATUSES[$newStatus] ?? 'that status') . '.'
                );
            }

            // Cancelling puts the pieces back on the shelf
            if ($newStatus === 'cancelled') {
                $this->returnStock($order);
            }

            $order->status = $newStatus;

            // Cash on delivery is paid when the order is delivered
            if ($newStatus === 'delivered') {
                $order->payment_status = 'paid';
            }
            if ($newStatus === 'refunded') {
                $order->payment_status = 'refunded';
            }

            $order->save();

            // Log who did what. Only ids and statuses: no personal details.
            Log::info('Order status changed', [
                'order'   => $order->order_number,
                'from'    => $from,
                'to'      => $newStatus,
                'by_user' => $adminId,
            ]);

            return $order;
        });
    }

    // Adds the ordered quantities back to each product's stock
    private function returnStock(Order $order): void
    {
        foreach ($order->items as $item) {
            // product_id is empty if the product was deleted later: nothing to restock then
            if ($item->product_id) {
                Product::whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        }
    }
}

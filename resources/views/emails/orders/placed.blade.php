<x-mail::message>
    # Thank you for your order, {{ $order->customer_name }}!

    We received your order **{{ $order->order_number }}**. We will call you on {{ $order->customer_phone }} to confirm delivery.

    <x-mail::table>
        | Product | Qty | Total |
        | :------ | :-: | ----: |
        @foreach ($order->items as $item)
        | {{ str_replace('|', '/', $item->product_name) }} | {{ $item->quantity }} | {{ config('shop.currency') }} {{ number_format($item->line_total) }} |
        @endforeach
    </x-mail::table>

    **Subtotal:** {{ config('shop.currency') }} {{ number_format($order->subtotal) }}

    **Shipping:** {{ $order->shipping_fee > 0 ? config('shop.currency') . ' ' . number_format($order->shipping_fee) : 'Free' }}

    **Total to pay:** {{ config('shop.currency') }} {{ number_format($order->total) }}

    **Payment:** {{ $order->paymentLabel() }}

    **Delivery address:** {{ $order->customer_name }}, {{ $order->shipping_address }}, {{ $order->shipping_city }}, {{ $order->shipping_country }}

    <x-mail::button :url="route('account.orders.show', $order->order_number)">
        View your order
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
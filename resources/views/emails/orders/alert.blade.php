<x-mail::message>
    # New order received

    **Order:** {{ $order->order_number }}

    **Customer:** {{ $order->customer_name }}

    **Phone:** {{ $order->customer_phone }}

    **Email:** {{ $order->customer_email }}

    **City:** {{ $order->shipping_city }}

    **Items:** {{ $order->items->sum('quantity') }}

    **Total:** {{ config('shop.currency') }} {{ number_format($order->total) }} ({{ $order->paymentLabel() }})

    <x-mail::button :url="route('admin.orders.show', $order->order_number)">
        Open the order
    </x-mail::button>

    {{ config('app.name') }}
</x-mail::message>
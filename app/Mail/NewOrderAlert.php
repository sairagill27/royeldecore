<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAlert extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New order ' . $this->order->order_number . ' (' . config('shop.currency') . ' ' . number_format($this->order->total) . ')'
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.orders.alert');
    }
}

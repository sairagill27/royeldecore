<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// A short "something is waiting" email to the shop owner
class AdminAlertMail extends Mailable
{
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $lines,
        public ?string $url = null,
        public string $button = 'Open the admin panel',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[' . config('app.name') . '] ' . $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-alert');
    }
}
<?php

namespace App\Http\Controllers;

use App\Mail\ContactReceived;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    // Show the contact page
    public function show()
    {
        return view('pages.contact');
    }

    // Save the message
    public function store(Request $request)
    {
        // SPAM TRAP: the field "website" is hidden from people. A bot fills it in.
        // We pretend everything worked, so the bot learns nothing.
        if (filled($request->input('website'))) {
            return $this->thanks();
        }

        // Tidy the values: extra spaces and line breaks are removed from single-line fields
        $request->merge([
            'name'    => Str::squish((string) $request->input('name')),
            'email'   => Str::lower(trim((string) $request->input('email'))),
            'subject' => Str::squish((string) $request->input('subject')),
        ]);

        // SECURITY: every value is checked on the server
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $contact = ContactMessage::create($data);

        // Email you. If sending fails, the message is still saved in the inbox.
        try {
            $to = config('shop.admin_email') ?: config('mail.from.address');
            Mail::to($to)->send(new ContactReceived($contact));
        } catch (\Throwable $e) {
            Log::error('Contact email failed', ['message_id' => $contact->id, 'error' => $e->getMessage()]);
        }

        return $this->thanks();
    }

    private function thanks()
    {
        return redirect()->route('contact')
            ->with('success', 'Thank you! We received your message and will reply by email.')
            ->with('alert_title', 'Message sent');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        // SPAM TRAP: the field "company" is hidden from people. A bot fills it in.
        // We pretend it worked, so the bot learns nothing.
        if (filled($request->input('company'))) {
            return $this->thanks();
        }

        $raw   = $request->input('email');
        $email = is_string($raw) ? Str::lower(trim($raw)) : '';

        // The form sits in the footer of every page, so a problem is shown as a popup
        $check = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        if ($check->fails()) {
            return back()
                ->with('error', 'Please enter a valid email address.')
                ->with('alert_title', 'Check your email');
        }

        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if (! $subscriber) {
            NewsletterSubscriber::create(['email' => $email, 'token' => Str::random(64)]);
        } elseif ($subscriber->unsubscribed_at) {
            // They unsubscribed before and now want to come back
            $subscriber->unsubscribed_at = null;
            $subscriber->save();
        }

        // The same answer in every case: nobody can find out who is already subscribed
        return $this->thanks();
    }

    // The link every newsletter will contain: /newsletter/unsubscribe/{secret}
    public function unsubscribe(string $token)
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();

        if (! $subscriber->unsubscribed_at) {
            $subscriber->unsubscribed_at = now();
            $subscriber->save();
        }

        return redirect('/')
            ->with('success', 'You have been unsubscribed and will not receive our newsletter.')
            ->with('alert_title', 'Unsubscribed');
    }

    private function thanks()
    {
        return back()
            ->with('success', 'Thank you for subscribing to our newsletter!')
            ->with('alert_title', 'Subscribed');
    }
}

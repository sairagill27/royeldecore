<?php

namespace App\Http\Middleware;

use App\Services\TwoFactor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Runs after the login and "staff" checks on every admin page.
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);   // the login check handles this
        }

        // Two-step login is on: the code must have been typed in this session
        if (TwoFactor::isOn($user)) {
            if ((int) $request->session()->get('two_factor_ok') !== (int) $user->id) {
                if ($request->isMethod('GET')) {
                    $request->session()->put('two_factor_intended', $request->fullUrl());
                }

                return redirect()->route('admin.two-factor.challenge');
            }

            return $next($request);
        }

        // Not on yet, but the shop requires it: send them to set it up
        if (config('security.two_factor_required') && ! $request->routeIs('admin.two-factor.*')) {
            return redirect()->route('admin.two-factor.setup')
                ->with('error', 'Please turn on two-step login to use the admin panel.')
                ->with('alert_title', 'Two-step login needed');
        }

        return $next($request);
    }
}
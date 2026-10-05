<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Stops other websites from showing your site inside a hidden frame (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Stops browsers from guessing file types
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // Limits what address information is sent to other sites
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Turns off browser features your site does not use
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only active staff or admin accounts may pass.
        // Customers get a 403 "Forbidden" page, even if they type the URL.
        abort_unless($user && $user->is_active && $user->isStaff(), 403);

        return $next($request);
    }
}

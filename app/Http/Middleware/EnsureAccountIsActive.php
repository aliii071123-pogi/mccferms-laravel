<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_suspended) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'This account has been suspended.'], 403);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->withErrors(['account' => 'This account has been suspended.']);
    }
}

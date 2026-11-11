<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role === 'student' && (! $user->organization || ! $user->position) && ! $request->routeIs('profile.*')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Complete your student organization profile before making reservations.',
                    'profile_required' => true,
                ], 403);
            }

            return to_route('profile.edit');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Helpers\Response as HelpersResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthOrGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated (via Sanctum)
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Otherwise, check for X-Guest-ID header
        $guestId = $request->header('X-Guest-ID');

        if (empty($guestId)) {
            return HelpersResponse::error(401, "Unauthorized: Missing authentication or guest ID.");
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $guestId)) {
            return HelpersResponse::error(400, "Invalid Guest ID format.");
        }

        // Continue request (and attach guestId to request)
        $request->merge(['guest_id' => $guestId]);

        return $next($request);
    }
}

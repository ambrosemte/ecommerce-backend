<?php

namespace App\Http\Middleware;

use App\Helpers\Response as HelpersResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthOrGuest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        // If a bearer token is present, try to authenticate the user
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->tokenable) {
                Auth::login($accessToken->tokenable);
                return $next($request);
            }
        }

        // Otherwise, check for X-Guest-ID header
        $guestId = $request->header('X-Guest-ID');

        if (empty($guestId)) {
            return HelpersResponse::error(401, "Unauthorized: Missing authentication or guest ID.");
        }

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $guestId)) {
            return HelpersResponse::error(400, "Invalid Guest ID format.");
        }

        // Attach guest ID to request for controllers
        $request->merge(['guest_id' => $guestId]);

        return $next($request);
    }
}

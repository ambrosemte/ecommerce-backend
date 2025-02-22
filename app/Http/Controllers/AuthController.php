<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Trait\AuthTrait;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Response;
use Carbon\Carbon;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $loginData = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);
        if (!Auth::attempt($loginData)) {
            return Response::error(message: "Invalid credentials");
        }
        return AuthTrait::login(Auth::id());
    }

    public function loginViaGoogle(Request $request): JsonResponse
    {
        $request->validate([
            "id_token" => "required|string",
            "access_token" => "required|string",
        ]);

        $accessToken = $request['access_token'];

        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);

            // Fetch Google public keys
            // $keys = Http::get('https://www.googleapis.com/oauth2/v3/certs')->json();
            //$algorithms = ['RS256']; // Define algorithms as an array

            // Parse JWKs into a format usable by JWT::decode
            //$parsedKeys = JWK::parseKeySet($keys);

            // Decode the ID token
            //$decodedToken = JWT::decode($idToken, $parsedKeys);

            // Validate audience (your Web Client ID)
            // $webClientId = config('services.google.client_id'); // Set in .env
            // if ($decodedToken->aud !== $webClientId) {
            //     return Response::error(message: "Invalid audience");
            // }

            // Token is valid, authenticate the user
            $user = User::where('google_id', $googleUser->getId())->orWhere('email', $googleUser->getEmail())->first();

            if ($user) {
                // If the Google ID matches or the email exists in the database, link Google ID if not already linked
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }

                return AuthTrait::login($user->id);
            } else {
                // Register a new user if no match is found
                $registerData = [
                    "google_id" => $googleUser->getId(),
                    "name" => $googleUser->getName(),
                    "email" => $googleUser->getEmail(),
                    "image_url" => $googleUser->getAvatar(),
                    "email_verified_at" => $googleUser->user['email_verified'] ? now() : null,
                    "password" => bcrypt(Str::random(16)),

                ];

                return AuthTrait::register($registerData);
            }

        } catch (\Exception $e) {
            return Response::error(message: "Token verification failed", data: [$e->getMessage()]);
        }
    }

    public function loginViaFacebook(Request $request): JsonResponse
    {
        $request->validate([
            "id_token" => "required|string",
            "access_token" => "required|string",
        ]);

        $accessToken = $request['access_token'];

        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->userFromToken($accessToken);

            // Token is valid, authenticate the user
            $user = User::where('facebook_id', $facebookUser->getId())->orWhere('email', $facebookUser->getEmail())->first();

            if ($user) {
                // If the Facebook ID matches or the email exists in the database, link Facebook ID if not already linked
                if (!$user->google_id) {
                    $user->update(['facebook_id' => $facebookUser->getId()]);
                }

                return AuthTrait::login($user->id);
            } else {
                // Register a new user if no match is found
                $registerData = [
                    "facebook_id" => $facebookUser->getId(),
                    "name" => $facebookUser->getName(),
                    "email" => $facebookUser->getEmail(),
                    "image_url" => $facebookUser->getAvatar(),
                    "email_verified_at" => $facebookUser->user['email_verified'] ? now() : null,
                    "password" => bcrypt(Str::random(16)),

                ];

                return AuthTrait::register($registerData);
            }

        } catch (\Exception $e) {
            return Response::error(message: "Token verification failed", data: [$e->getMessage()]);
        }
    }

    public function register(Request $request)
    {
        $registerData = $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|email|max:255",
            "password" => ["required", Password::min(6)],
            "phone" => "required|numeric",
        ]);
        return AuthTrait::register($registerData);
    }

    public function logout()
    {
        Auth::logout();

        return Response::success(message: "Logout successful");
    }
}

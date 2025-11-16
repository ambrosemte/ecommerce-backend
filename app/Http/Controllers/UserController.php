<?php

namespace App\Http\Controllers;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Http\Resources\DeliveryDetailResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get authenticated users profile
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        $user = User::find(Auth::id());

        $data = [
            "user" => new UserResource(Auth::user()),
            "deliveryDetails" => $user->deliveryDetails()->exists()
                ? DeliveryDetailResource::collection($user->deliveryDetails()->get())
                : []
        ];

        return Response::success(message: "User profile retrieved", data: $data);
    }

    /**
     * Update authenticated user profile image
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfileImage(Request $request)
    {
        $validated = $request->validate([
            "image" => "required|file|mimes:png,jpg,jpeg|max:5120",
        ]);

        try {
            $this->userService->updateProfileImage($validated);
            return Response::success(message: "Profile image updated");
        } catch (\Exception $e) {
            return Response::error(statusCode: 500, message: "Failed to update profile image. Error: " . $e->getMessage());
        }
    }

    /**
     * Check if user is authenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAuthentication()
    {
        if (!Auth::check()) {
            return Response::error(statusCode: 401, message: 'Unauthenticated');
        }
        return Response::success();
    }


    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            "phone" => "required|numeric",
        ]);

        try {
            $this->userService->updateProfile($validated);
            return Response::success(message: "Profile updated");
        } catch (\Exception $e) {
            return Response::error(statusCode: 400, message: $e->getMessage());
        }
    }

    /**
     * Set authenticated user preferred currency
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPreferredCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        try {
            $this->userService->setPreferredCurrency($validated);
            return Response::success(message: "Preferred currency updated");
        } catch (\Exception $e) {
            return Response::error(statusCode: 400, message: $e->getMessage());
        }
    }

    /**
     * Upate authenticated users firebase device token
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFirebaseToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $this->userService->updateFirebaseToken($validated);
            return Response::success(message: "Firebase token updated");
        } catch (\Exception $e) {
            return Response::error(statusCode: 400, message: $e->getMessage());
        }
    }

    public function getUsers()
    {
        try {
            $data = $this->userService->getUsers();
            return Response::success(message: 'Users retrieved', data: $data);
        } catch (\Exception $e) {
            return Response::error(statusCode: 400, message: $e->getMessage());
        }
    }
}

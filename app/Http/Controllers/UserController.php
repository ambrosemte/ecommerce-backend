<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Http\Resources\DeliveryDetailResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function getProfile()
    {

        $data = [
            "user" => new UserResource(Auth::user()),
            "deliveryDetails" => Auth::user()->deliveryDetails()->exists()
                ? DeliveryDetailResource::collection(Auth::user()->deliveryDetails()->get())
                : []
        ];

        return Response::success(message: "User profile retrieved", data: $data);
    }

    public function updateProfileImage(Request $request)
    {
        $request->validate([
            "image" => "required|file|mimes:png,jpg,jpeg|max:5120",
        ]);

        try {
            $imagePath = $request->file('image')->store("profile-images", "public");

            $oldImagePath = Auth::user()->image_url;
            if ($oldImagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            User::where('id', Auth::id())->update(['image_url' => $imagePath]);

            return Response::success(message: "Profile image updated");
        } catch (\Exception $e) {
            return Response::error(message: "Failed to update profile image. Error:" . $e->getMessage());
        }
    }

    public function checkAuthentication()
    {
        if (Auth::check()) {
            return Response::success();
        }
    }


    public function updateProfile(Request $request)
    {
        $request->validate([
            "phone" => "required|numeric",
        ]);

        Auth::user()->update(["phone" => $request['phone']]);

        return Response::success(message: "Profile updated");
    }
}

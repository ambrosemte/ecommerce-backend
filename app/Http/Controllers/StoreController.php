<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Response;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::where('user_id', Auth::id())
            ->with(['products.productVariations.productMedia'])
            ->withCount('followers')
            ->get(); // Get all stores instead of just one

        if ($stores->isEmpty()) {
            return Response::notFound(message: "No stores found for this user");
        }
       
        return Response::success(message: "Stores retireved", data: $stores->toArray());
    }

    public function show(string $id)
    {
        $store = Store::with(['products.productVariations.productMedia'])
            ->withCount('followers')
            ->find($id);

        if (!$store) {
            return Response::notFound(message: "Store not found");
        }

        $store->products->transform(function ($product) {
            // Check if the product is in the user's wishlist
            if (auth('sanctum')->check()) {
                $user = User::find(auth('sanctum')->id());
                $isWished = $user->wishlists()->where('product_id', $product->id)->exists();

            }
            // Add wishedlist field to the product object
            $product->wished_list = $isWished ?? false;

            return $product;
        });

        return Response::success(message: "Store retireved", data: $store->toArray());
    }

    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:100",
            "image" => "required|max:5120|mimes:png,jpg,jpeg"
        ]);
        $user = User::find(Auth::id());

        $imagePath = $request->file("image")->store("store-image", "public");

        $user->stores()->create([
            "name" => $request['name'],
            "image_url" => $imagePath,
        ]);

        return Response::success(message: "Store created");
    }

    public function follow(string $storeId)
    {
        $store = Store::find($storeId);

        if (!$store) {
            return Response::notFound(message: "Store not found");
        }
        try {
            $store->follow(Auth::user());
        } catch (\Exception) {
            return Response::error(message: "Already following store");
        }

        return Response::success(message: "Store followed");
    }

    public function delete(string $id)
    {
        $store = Store::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$store) {
            return Response::notFound(message: "Store not found.");
        }

        $store->delete();

        return Response::success(message: "Store deleted successfully.");
    }
}

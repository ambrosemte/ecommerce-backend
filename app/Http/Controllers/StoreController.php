<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
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
            $isWished = Auth::user()->wishlists()->where('product_id', $product->id)->exists();

            // Add wished_list field to the product object
            $product->wished_list = $isWished;

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

        $imagePath = $request->file("image")->store("store-image", "public");

        Auth::user()->stores()->create([
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
}

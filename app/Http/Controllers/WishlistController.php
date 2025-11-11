<?php

namespace App\Http\Controllers;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function index()
    {
        // Determine source of wishlist
        if (auth('sanctum')->check()) {

            $user = User::find(auth('sanctum')->id());

            $wishlistItems = $user->wishlists()
                ->latest()
                ->get(['id', 'product_id', 'product_variation_id']);
        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Wishlist->format($guestId);
            $wishlistItems = Cache::get($sessionKey, []);

            $wishlistItems = collect($wishlistItems); // make sure it's a Collection
        }

        // Extract all product IDs
        $productIds = $wishlistItems->pluck('product_id')->toArray();

        // Fetch products along with variations and media
        $products = Product::with('productVariations.productMedia')
            ->whereIn('id', $productIds)
            ->get();

        // Match each product with the variation from the wishlist
        $wislist = $wishlistItems->map(function ($wishlistItem) use ($products) {
            $product = $products->firstWhere('id', $wishlistItem['product_id'] ?? $wishlistItem->product_id);

            if ($product) {
                $storedVariationId = $wishlistItem['product_variation_id'] ?? $wishlistItem->product_variation_id ?? null;
                $productVariation = $product->productVariations
                    ->firstWhere('id', $storedVariationId);

                // Assign selected variation
                $product->product_variation = $productVariation;

                // Remove original variations
                unset($product->productVariations);
            }

            return [
                'id' => $wishlistItem['id'] ?? $wishlistItem->id ?? null,
                'product' => $product,
            ];
        });

        return Response::success(message: "Wishlist retrieved", data: $wislist->toArray());
    }


    public function show($id)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $wishlist = User::find($userId)->wishlists()->where('id', $id)->first();

            if (!$wishlist) {
                return Response::notFound(message: "Wishlist not found for this user");
            }

        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Wishlist->format($guestId);
            $ids = Cache::get($sessionKey, []);

            $wishlist = Wishlist::find($ids[0]);

        }

        return Response::success(message: "Wishlist retrieved", data: $wishlist->toArray());
    }

    public function store(Request $request)
    {
        $wishlistRequestData = $request->validate([
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
        ]);

        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            User::find($userId)
                ->wishlists()
                ->create($wishlistRequestData);

        } else {

            $guestId = $request->input('guest_id');
            $sessionKey = SessionKey::Wishlist->format($guestId);

            // Get current cached list (array of arrays)
            $recent = Cache::get($sessionKey, []);

            // Convert arrays to JSON strings for accurate comparison
            $itemJson = json_encode($wishlistRequestData);

            // Remove any existing entry that matches this one
            $recent = array_filter($recent, function ($existing) use ($itemJson) {
                return json_encode($existing) != $itemJson;
            });

            // Add new item to the beginning
            array_unshift($recent, $wishlistRequestData + ['id' => Str::uuid()]);

            // Reindex and store back in cache
            $recent = array_values($recent);
            Cache::put($sessionKey, $recent, now()->addDays(30));
        }

        return Response::success(message: "Product added to wishlist");
    }

    public function delete($id)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $wishlist = User::find($userId)
                ->wishlists()
                ->find($id);

            if (!$wishlist) {
                return Response::notFound(message: "Product not found in wishlist");
            }

            $wishlist->delete();
        } else {

            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Wishlist->format($guestId);
            $cachedWishlist = Cache::get($sessionKey, []);

            // Check if the product exists in the cache
            $exists = collect($cachedWishlist)->contains(fn($item) => $item['id'] == $id);

            if (!$exists) {
                return Response::notFound(message: "Product not found in guest wishlist");
            }

            // Remove the product
            $updatedWishlist = collect($cachedWishlist)
                ->reject(fn($item) => $item['id'] == $id)
                ->values()
                ->toArray();

            Cache::put($sessionKey, $updatedWishlist, now()->addDays(30));

        }

        return Response::success(message: "Product removed from wishlist");
    }
}

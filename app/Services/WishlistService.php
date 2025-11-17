<?php

namespace App\Services;

use App\Enums\SessionKey;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WishlistService
{
    public function getWishlistItems()
    {
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

        return $wislist->toArray();
    }

    public function addToWishlist(array $data)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
            $user = User::find($userId);

            $exists = $user->wishlists()
                ->where('product_id', $data['product_id'])
                ->where('product_variation_id', $data['product_variation_id'])
                ->exists();

            if (!$exists) {
                $user->wishlists()->create($data);
            }

        } else {
            $guestId = request()->input('guest_id');
            $sessionKey = SessionKey::Wishlist->format($guestId);

            // Get current cached list (array of arrays)
            $recent = Cache::get($sessionKey, []);

            // Convert arrays to JSON strings for accurate comparison
            $itemJson = json_encode($data);

            // Remove any existing entry that matches this one
            $recent = array_filter($recent, function ($existing) use ($itemJson) {
                return json_encode($existing) != $itemJson;
            });

            array_unshift($recent, $data + ['id' => (string) Str::uuid()]);

            $recent = array_values($recent);
            Cache::put($sessionKey, $recent, now()->addDays(30));
        }
    }

    public function removeFromWishlist(string $id, string $productId, string $productVariationId)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $query = User::find($userId)->wishlists();

            if ($id) {
                $wishlist = $query->find($id);
            } else {
                $wishlist = $query
                    ->where('product_id', $productId)
                    ->where('product_variation_id', $productVariationId)
                    ->first();
            }

            if (!$wishlist) {
                throw new Exception("Product not found in wishlist");
            }

            $wishlist->delete();

        } else {

            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Wishlist->format($guestId);
            $cachedWishlist = Cache::get($sessionKey, []);

            if ($id) {

                // Check if the product exists in the cache
                $exists = collect($cachedWishlist)->contains(fn($item) => $item['id'] == $id);

                if (!$exists) {
                    throw new Exception("Product not found in guest wishlist");
                }

                // Remove the product
                $updatedWishlist = collect($cachedWishlist)
                    ->reject(fn($item) => $item['id'] == $id)
                    ->values()
                    ->toArray();
            } else {
                $exists = collect($cachedWishlist)->contains(
                    fn($item) =>
                    $item['product_id'] == $productId && $item['product_variation_id'] == $productVariationId
                );

                if (!$exists) {
                    throw new Exception("Product not found in guest wishlist");
                }

                $updatedWishlist = collect($cachedWishlist)
                    ->reject(
                        fn($item) =>
                        $item['product_id'] == $productId && $item['product_variation_id'] == $productVariationId
                    )
                    ->values()
                    ->toArray();

            }

            Cache::put($sessionKey, $updatedWishlist, now()->addDays(30));

        }
    }

    public function syncFromGuest(string $userId, string $guestId)
    {
        $sessionKey = SessionKey::Cart->format($guestId);
        $items = Cache::get($sessionKey, []);

        if (!empty($items)) {
            $user = User::find($userId);

            foreach ($items as $item) {
                $user->carts()->updateOrCreate(
                    [
                        'product_id' => $item['product_id'],
                        'product_variation_id' => $item['product_variation_id'],
                    ],
                    ['quantity' => $item['quantity']]
                );
            }

            Cache::forget($sessionKey);
        }
    }
}

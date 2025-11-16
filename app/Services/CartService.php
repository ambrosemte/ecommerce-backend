<?php

namespace App\Services;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CartService
{

    public function getCartItems()
    {
        if (auth('sanctum')->check()) {

            $user = User::find(auth('sanctum')->id());

            $cartItems = $user->carts()
                ->latest()
                ->get(['id', 'product_id', 'product_variation_id', 'quantity']);
        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Cart->format($guestId);
            $cartItems = Cache::get($sessionKey, []);

            $cartItems = collect($cartItems); // make sure it's a Collection
        }

        // Extract all product IDs
        $productIds = $cartItems->pluck('product_id')->toArray();

        // Fetch products along with variations and media
        $products = Product::with('productVariations.productMedia')
            ->whereIn('id', $productIds)
            ->get();

        // Map cart items to include product, quantity, and cart item ID
        $cart = $cartItems->map(function ($cartItem) use ($products) {
            $product = $products->firstWhere('id', $cartItem['product_id'] ?? $cartItem->product_id);

            if ($product) {
                $storedVariationId = $cartItem['product_variation_id'] ?? $cartItem->product_variation_id ?? null;
                $productVariation = $product->productVariations
                    ->firstWhere('id', $storedVariationId);

                // Assign selected variation
                $product->product_variation = $productVariation;

                // Remove original variations
                unset($product->productVariations);
            }

            return [
                'id' => $cartItem['id'] ?? $cartItem->id ?? null,
                'quantity' => $cartItem['quantity'] ?? $cartItem->quantity ?? 1,
                'delivery_detail_id' => $cartItem['delivery_detail_id'] ?? $cartItem->delivery_detail_id ?? null,
                'product' => $product,
            ];
        });

        return [
            "cart" => $cart->toArray(),
            "cart_count" => $cartItems->count(),
        ];
    }

    public function addToCart(array $data)
    {
        $user = User::find(Auth::id());

        // Fetch the product variation
        $productVariation = ProductVariation::find($data['product_variation_id']);


        // Check if enough quantity is available
        if ($productVariation->quantity < $data['quantity']) {
            throw new Exception("Not enough stock available for this product.");
        }

        $productVariation->decrement('quantity', $data['quantity']);

        $cartData = [
            'store_id' => $data['store_id'],
            'product_id' => $data['product_id'],
            'product_variation_id' => $data['product_variation_id'],
            'quantity' => $data['quantity'],
            'delivery_detail_id' => $data['delivery_detail_id'] ?? null,
        ];

        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $cartItem = User::find($userId)->carts()
                ->where('product_id', $data['product_id'])
                ->where('product_variation_id', $data['product_variation_id'])
                ->first();

            if ($cartItem) {
                // Update quantity if item already exists
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $data['quantity'],
                ]);
            } else {
                // Add new item
                $user->carts()->create($cartData);
            }
        } else {
            // Handle guest carts with cache
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Cart->format($guestId);
            $cart = Cache::get($sessionKey, []);

            // Check if item already exists (same product + variation)
            $existingIndex = collect($cart)->search(
                fn($item) =>
                $item['product_id'] == $data['product_id'] &&
                $item['product_variation_id'] == $data['product_variation_id']
            );

            if ($existingIndex) {
                $cart[$existingIndex]['quantity'] += $data['quantity'];
            } else {
                array_unshift($cart, $cartData + ['id' => (string) Str::uuid()]);
            }

            // Store updated cart for 30 days
            Cache::put($sessionKey, $cart, now()->addDays(30));
        }
    }

    public function updateCart(array $data)
    {
        $user = User::find(Auth::id());

        $cartItems = $data['cart'];

        foreach ($cartItems as $item) {
            $user->carts()
                ->where('product_id', $item['product_id'])
                ->where('product_variation_id', $item['product_variation_id'])
                ->update([
                    'quantity' => $item['quantity'],
                ]);
        }

    }

    public function removeFromCart(string $id)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $user = User::find($userId);

            $cart = $user->carts()->find($id);

            if (!$cart) {
                throw new Exception("Product not found in cart");
            }

            $productVariation = ProductVariation::find($cart->product_variation_id);

            $productVariation->increment('quantity', $cart->quantity);

            $cart->delete();

        } else {
            $guestId = request()->input('guest_id');

            $sessionKey = SessionKey::Cart->format($guestId);
            $cachedCart = Cache::get($sessionKey, []);

            // Check if the product exists in the cache
            $exists = collect($cachedCart)->contains(fn($item) => $item['id'] == $id);

            if (!$exists) {
                throw new Exception("Product not found in guest cart");
            }

            // Remove from cart
            $updatedCart = collect($cachedCart)
                ->reject(fn($item) => $item['id'] == $id)
                ->values()
                ->toArray();

            Cache::put($sessionKey, $updatedCart, now()->addDays(30));
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
                        'store_id' => $item['store_id']
                    ],
                    ['quantity' => $item['quantity']]
                );
            }

            Cache::forget($sessionKey);
        }
    }
}

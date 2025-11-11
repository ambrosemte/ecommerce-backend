<?php

namespace App\Http\Controllers;

use App\Enums\SessionKey;
use App\Helpers\Response;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
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

        $data = [
            "cart" => $cart->toArray(),
            "cart_count" => $cartItems->count(),
        ];

        return Response::success(message: "Cart retrieved", data: $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            "store_id" => "required|exists:stores,id",
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
            "quantity" => "required|numeric|min:1|max_digits:11",
            "delivery_detail_id" => "nullable|exists:delivery_details,id",
        ]);

        $user = User::find(Auth::id());

        // Fetch the product variation
        $productVariation = ProductVariation::find($request['product_variation_id']);


        // Check if enough quantity is available
        if ($productVariation->quantity < $request['quantity']) {
            return Response::error(400, "Not enough stock available for this product.");
        }

        $productVariation->decrement('quantity', $request['quantity']);

        $cartData = [
            'store_id' => $request['store_id'],
            'product_id' => $request['product_id'],
            'product_variation_id' => $request['product_variation_id'],
            'quantity' => $request['quantity'],
            'delivery_detail_id' => $request['delivery_detail_id'] ?? null,
        ];

        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $cartItem = User::find($userId)->carts()
                ->where('product_id', $request['product_id'])
                ->where('product_variation_id', $request['product_variation_id'])
                ->first();

            if ($cartItem) {
                // Update quantity if item already exists
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $request['quantity'],
                ]);
            } else {
                // Add new item
                $user->carts()->create($cartData);
            }
        } else {
            // Handle guest carts with cache
            $guestId = $request->get('guest_id');

            $sessionKey = SessionKey::Cart->format($guestId);
            $cart = Cache::get($sessionKey, []);

            // Check if item already exists (same product + variation)
            $existingIndex = collect($cart)->search(
                fn($item) =>
                $item['product_id'] == $request['product_id'] &&
                $item['product_variation_id'] == $request['product_variation_id']
            );

            if ($existingIndex) {
                $cart[$existingIndex]['quantity'] += $request['quantity'];
            } else {
                array_unshift($cart,$cartData + ['id' => Str::uuid()]);
            }

            // Store updated cart for 30 days
            Cache::put($sessionKey, $cart, now()->addDays(30));
        }

        return Response::success(message: "Product added to  cart");
    }

    public function update(Request $request)
    {
        $request->validate([
            "cart" => "array|required",
        ]);

        $user = User::find(Auth::id());

        $cartItems = $request['cart'];

        foreach ($cartItems as $item) {
            $user->carts()
                ->where('product_id', $item['product_id'])
                ->update(['quantity' => $item['quantity']]);
        }

        return Response::success(message: "Cart updated successfully");
    }

    public function delete(string $id)
    {
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();

            $user = User::find($userId);

            $cart = $user->carts()->find($id);

            if (!$cart) {
                return Response::notFound(message: "Product not found in cart");
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
                return Response::notFound(message: "Product not found in guest cart ");
            }

            // Remove from cart
            $updatedCart = collect($cachedCart)
                ->reject(fn($item) => $item['id'] == $id)
                ->values()
                ->toArray();

            Cache::put($sessionKey, $updatedCart, now()->addDays(30));
        }

        return Response::success(message: "Product removed from cart");
    }

}

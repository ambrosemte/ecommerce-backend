<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::id());

        $cart = $user->carts()->with(["product", "productVariation.productMedia"])
            ->get();

        $data = [
            "cart" => $cart->toArray(),
            "cart_count" => $cart->count(),
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


        // ✅ Check if enough quantity is available
        if ($productVariation->quantity < $request['quantity']) {
            return Response::error(400, "Not enough stock available for this product.");
        }

        $productVariation->decrement('quantity', $request['quantity']);


        $cartItem = $user->carts()
            ->where('product_id', $request['product_id'])
            ->first();

        if ($cartItem) {
            // Update quantity if item already in cart
            $cartItem->update([
                'quantity' => $cartItem->quantity + $request['quantity'],
            ]);
        } else {
            // Add new item to cart
            $user->carts()->create([
                'user_id' => Auth::id(),
                'store_id' => $request['store_id'],
                'product_id' => $request['product_id'],
                'product_variation_id' => $request['product_variation_id'],
                'quantity' => $request['quantity'],
            ]);
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
        $user = User::find(Auth::id());

        $cart = $user->carts()->where('id', $id)->first();

        if (!$cart) {
            return Response::notFound(message: "Product not found for this user");
        }

        $cart->delete();

        $productVariation = ProductVariation::find($cart->product_variation_id);

        $productVariation->increment('quantity', $cart->quantity);


        return Response::success(message: "Product removed from cart");
    }

}

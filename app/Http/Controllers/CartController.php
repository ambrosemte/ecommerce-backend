<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = Auth::user()->carts()->with(["product", "productVariation.productMedia"])
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
            "product_id" => "required|exists:products,id",
            "product_variation_id" => "required|exists:product_variations,id",
            "quantity" => "required|numeric|min:1|max_digits:11",
            "delivery_detail_id" => "nullable|exists:delivery_details,id",
        ]);

        $cartItem = Auth::user()->carts()
            ->where('product_id', $request['product_id'])
            ->first();

        if ($cartItem) {
            // Update quantity if item already in cart
            $cartItem->update([
                'quantity' => $cartItem->quantity + $request['quantity'],
            ]);
        } else {
            // Add new item to cart
            Auth::user()->carts()->create([
                'user_id' => Auth::id(),
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

        $cartItems = $request['cart'];

        foreach ($cartItems as $item) {
            Auth::user()->carts()
                ->where('product_id', $item['product_id'])
                ->update(['quantity' => $item['quantity']]);
        }

        return Response::success(message: "Cart updated successfully");
    }

    public function delete(string $id)
    {
        $cart = Auth::user()->carts()->where('id', $id)->first();

        if (!$cart) {
            return Response::notFound(message: "Product not found for this user");
        }

        $cart->delete();

        return Response::success(message: "Product removed from cart");
    }

}

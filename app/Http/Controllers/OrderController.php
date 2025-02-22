<?php

namespace App\Http\Controllers;

use App\Enums\OrderCategoryEnum;
use App\Enums\OrderStatusEnum;
use App\Helpers\Response;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()->with(["orderItems.product", "orderItems.productVariation.productMedia"])
            ->get()
            ->toArray();

        return Response::success(message: "Order retrieved", data: $orders);

    }


    public function store(Request $request)
    {

        $cartItems = Auth::user()->carts()->with(['product', 'productVariation'])
            ->get();

        if ($cartItems->isEmpty()) {
            return Response::error(message: "Cart is empty");
        }

        // Calculate total price
        $totalPrice = $cartItems->sum(function ($cartItem) {
            return $cartItem->quantity * $cartItem->productVariation->price;
        });

        // Create order
        $order = Auth::user()->orders()->create([
            'status' => OrderStatusEnum::ORDERPLACED,
            'total_price' => $totalPrice,
        ]);

        // Add items to order_items
        foreach ($cartItems as $cartItem) {
            $order->orderItems()->create([
                'product_id' => $cartItem->product_id,
                'product_variation_id' => $cartItem->product_variation_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->productVariation->price,
            ]);
        }

        // Clear cart
        $cartItem->delete();

        return Response::success(message: "Order placed");
    }
    ///continue

    public function cancelOrder(string $id)
    {
        $order = Auth::user()->orders()->where('id', $id)->where('category', OrderCategoryEnum::TORECEIVE)->first();

        if (!$order) {
            return Response::notFound(message: "Order not found for this user");
        }

        $order->update(['status' => OrderStatusEnum::CANCELLED]);

        return Response::success(message: "Order cancelled");
    }
}

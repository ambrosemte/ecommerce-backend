<?php

namespace App\Http\Controllers;

use App\Enums\OrderCategoryEnum;
use App\Enums\OrderStatusEnum;
use App\Helpers\OrderHelper;
use App\Helpers\Response;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function getToReceiveOrders()
    {
        $user = User::find(Auth::id());
        $orders = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDERPLACED,
                    OrderStatusEnum::ORDERCONFIRMED,
                    OrderStatusEnum::PROCESSING,
                    OrderStatusEnum::SHIPPED,
                    OrderStatusEnum::OUTFORDELIVERY
                ]);
            })
            ->with([
                'product.productVariations.productMedia',
                'latestStatus'
            ])
            ->get()
            ->toArray();

        return Response::success(message: "To receive orders retrieved", data: $orders);
    }

    public function getCancelledOrders()
    {
        $user = User::find(Auth::id());
        $orders = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::CANCELLED,
                    OrderStatusEnum::RETURNED,
                    OrderStatusEnum::REFUNDED,
                    OrderStatusEnum::FAILED,
                ]);
            })
            ->with([
                'product.productVariations.productMedia',
                'latestStatus'
            ])
            ->get()
            ->toArray();

        return Response::success(message: "Cancelled/Refunded orders retrieved", data: $orders);

    }

    public function show($id)
    {

        $user = User::find(Auth::id());

        $query = Order::query()
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('tracking_id', $id);
            });


        if (!$user->hasAnyRole(['admin', 'agent'])) {
            $query->where('user_id', $user->id);
        }

        $order = $query->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        $order->load([
            'product',
            'product.productVariations.productMedia',
            'latestStatus',
            'statuses',
        ]);

        return Response::success(message: "Order retrieved", data: $order->toArray());



    }


    public function store(Request $request)
    {
        $user = User::find(Auth::id());
        $cartItems = $user->carts()->with(['product', 'productVariation'])
            ->get();

        if ($cartItems->isEmpty()) {
            return Response::error(message: "Cart is empty");
        }

        // Create order
        foreach ($cartItems as $cartItem) {
            $order = $user->orders()->create([
                'store_id' => $cartItem->store_id,
                'product_id' => $cartItem->product_id,
                'product_variation_id' => $cartItem->product_variation_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->productVariation->price,
            ]);

            OrderStatus::create([
                'order_id' => $order->id,
                'status' => OrderStatusEnum::ORDERPLACED,
                'description' => OrderHelper::getOrderStatusText(OrderStatusEnum::ORDERPLACED),
                'changed_by' => Auth::id(),
            ]);
        }

        // Clear cart
        $cartItem->delete();

        return Response::success(message: "Order placed");
    }

    public function cancelOrder(string $id)
    {
        $user = User::find(Auth::id());
        $order = $user->orders()->where('id', $id)->where('category', OrderCategoryEnum::TORECEIVE)->first();

        if (!$order) {
            return Response::notFound(message: "Order not found for this user");
        }

        $order->update(['status' => OrderStatusEnum::CANCELLED]);

        return Response::success(message: "Order cancelled");
    }

    public function getAdminSellerOrders()
    {
        $user = User::find(Auth::id());

        $query = Order::with([
            'product',
            'product.productVariations' => function ($query) {
                $query->whereHas('orders'); // Only variations that were ordered
            },
            'product.productVariations.productMedia',
            'store',
            'latestStatus'
        ]);

        // If seller or agent — limit to their store
        if ($user->hasRole('seller')) {
            $query->whereHas('store', function ($storeQuery) use ($user) {
                $storeQuery->where('user_id', $user->id);
            });

            $orders = $query->get(); // no pagination for seller
        }

        // Admin or Agent: all orders with pagination
        else if ($user->hasAnyRole(['admin', 'agent'])) {
            $orders = $query->paginate(15);
        }

        return Response::success(message: "Admin/Seller Orders retrieved successfully", data: $orders->toArray());
    }


    public function acceptOrder(string $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::ORDERCONFIRMED,
            'description' => OrderHelper::getOrderStatusText(OrderStatusEnum::ORDERCONFIRMED),
            'changed_by' => Auth::id(),
        ]);

        return Response::success(message: "Order accepted");
    }

    public function declineOrder(string $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        OrderStatus::create([
            'order_id' => $order->id,
            'status' => OrderStatusEnum::CANCELLED,
            'description' => OrderHelper::getOrderStatusText(OrderStatusEnum::CANCELLED),
            'changed_by' => Auth::id(),
        ]);
        return Response::success(message: "Order declined");
    }
}

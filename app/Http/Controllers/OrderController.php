<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function getToReceiveOrders()
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->getToReceiveOrders($user);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "To receive orders retrieved", data: $data);
    }

    public function getCancelledOrders()
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->getToCancelledOrders($user);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Cancelled/Refunded orders retrieved", data: $data);
    }

    public function getOrdersWithoutReview()
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->ordersWithoutReview($user);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Orders without review retrieved", data: $data);
    }

    public function show($id)
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->getOrder($user, $id);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order retrieved", data: $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            "shipping_method_id" => "required|string|exists:shipping_methods,id",
            "delivery_detail_id" => "required|string|exists:delivery_details,id",
        ]);

        $user = User::find(Auth::id());

        try {
            $this->orderService->placeOder(
                $user,
                $request['delivery_detail_id'],
                $request['shipping_method_id']
            );
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order placed");
    }

    public function cancelOrder(string $id)
    {
        $user = User::find(Auth::id());

        $order = $user->orders()
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('tracking_id', $id);
            })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->cancelOrder($order, $user->id);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order cancelled");
    }

    public function requestRefund(string $id)
    {
        $user = User::find(Auth::id());

        $order = $user->orders()
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('tracking_id', $id);
            })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found for this user");
        }

        try {
            $this->orderService->requestRefund($order, $user->id);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Refund has been requested");
    }

    public function getOrders()
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->getOrders($user);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Orders retrieved successfully", data: $data);
    }

    public function getOrdersActivity()
    {
        $user = User::find(Auth::id());

        try {
            $data = $this->orderService->getOrdersActivity($user);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Orders activity retrieved successfully", data: $data);
    }



    //SELLER
    public function acceptOrder(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        if ($order->store->user_id != Auth::id()) {
            return Response::error(message: "You are not authorized to accept this order");
        }

        try {
            $this->orderService->acceptOrder($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order accepted");
    }

    public function declineOrder(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        if ($order->store->user_id != Auth::id()) {
            return Response::error(message: "You are not authorized to decline this order");
        }

        try {
            $this->orderService->declineOrder($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order declined");
    }

    //AGENT
    public function processOrder(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->processOrder($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order is now processing at the hub");
    }

    public function shipOrder(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->shipOrder($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order is now shipped");
    }

    //AGENT
    public function outForDelivery(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->outForDelivery($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order is now Out for Delivery");
    }

    //AGENT
    public function markAsDelivered(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->markAsDelivered($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Order has been successfully delivered");
    }

    public function approveRefund(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->approveRefund($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Refund request has been approved");
    }

    public function declineRefund(string $id)
    {
        $order = Order::where(function ($query) use ($id) {
            $query->where('id', $id)
                ->orWhere('tracking_id', $id);
        })->first();

        if (!$order) {
            return Response::notFound(message: "Order not found");
        }

        try {
            $this->orderService->declineRefund($order, Auth::id());
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }

        return Response::success(message: "Refund request has been declined");
    }

}

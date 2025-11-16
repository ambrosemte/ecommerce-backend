<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Models\Category;
use App\Models\DeliveryDetail;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function getToReceiveOrders(User $user)
    {
        $orders = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDER_PLACED,
                    OrderStatusEnum::ORDER_CONFIRMED,
                    OrderStatusEnum::PROCESSING,
                    OrderStatusEnum::SHIPPED,
                    OrderStatusEnum::OUT_FOR_DELIVERY
                ]);
            })
            ->with([
                'product.productVariations.productMedia',
                'productVariation.productMedia',
                'latestStatus',
                'shippingMethod',
            ])
            ->paginate(15);

        $orders->getCollection()->transform(function ($order) {
            if ($order->product && $order->productVariation) {
                $order->product->product_variation = $order->productVariation;
                unset($order->product->productVariations);
            }
            return $order;
        });

        return $orders->toArray();
    }

    public function getToCancelledOrders(User $user)
    {
        $orders = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDER_DECLINED,
                    OrderStatusEnum::CANCELLED,
                    OrderStatusEnum::RETURNED,
                    OrderStatusEnum::REFUNDED,
                    OrderStatusEnum::FAILED,
                ]);
            })
            ->with([
                'product.productVariations.productMedia',
                'productVariation.productMedia',
                'latestStatus',
                'shippingMethod',
            ])
            ->paginate(15);

        $orders->getCollection()->transform(function ($order) {
            if ($order->product && $order->productVariation) {
                $order->product->product_variation = $order->productVariation;
                unset($order->product->productVariations);
            }
            return $order;
        });

        return $orders->toArray();
    }

    public function ordersWithoutReview(User $user)
    {
        $userId = $user->id;

        $orders = Order::where('user_id', $userId)
            ->whereHas('latestStatus', function ($query) {
                $query->where('status', OrderStatusEnum::DELIVERED);
            })
            ->whereDoesntHave('reviews', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'product',
                'product.productVariations.productMedia',
                'productVariation.productMedia'
            ]) // optional eager load
            ->paginate(15);

        $orders->getCollection()->transform(function ($order) {
            if ($order->product && $order->productVariation) {
                $order->product->product_variation = $order->productVariation;
                unset($order->product->productVariations);
            }
            return $order;
        });

        return $orders->toArray();
    }

    public function getOrder(User $user, string $id)
    {
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
            throw new Exception("Order not found");
        }

        $deliveryDetail = DeliveryDetail::find($order->delivery_detail_id);

        if (!$deliveryDetail) {
            throw new Exception("Delivery details not found");
        }

        $country = $deliveryDetail->country;
        $state = $deliveryDetail->state;
        $city = $deliveryDetail->city;

        $shippingZone = ShippingZone::where('country', $country)
            ->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', $state);
            })
            ->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', $city);
            })
            ->first();

        $shippingRate = ShippingRate::where('shipping_method_id', $order->shipping_method_id)
            ->where('shipping_zone_id', optional($shippingZone)->id)
            ->first();

        $order->load([
            'product',
            'product.productVariations.productMedia',
            'productVariation.productMedia',
            'latestStatus',
            'store',
            'statuses',
            'shippingMethod',
        ]);

        if ($order->product && $order->productVariation) {
            $order->product->product_variation = $order->productVariation;
            unset($order->product->productVariations); // optional
        }

        $order->shipping_zone = $shippingZone;
        $order->shipping_rate = $shippingRate;
        $order->delivery_detail = $deliveryDetail;
        $order->is_cancellable = $order->latestStatus->status == OrderStatusEnum::ORDER_PLACED;

        return $order->toArray();
    }

    public function placeOder(User $user, string $deliveryDetailId, string $shippingMethodId)
    {
        $cartItems = $user->carts()->with(['product', 'productVariation'])->get();

        if ($cartItems->isEmpty()) {
            throw new Exception("Cart is empty");
        }

        $deliveryDetail = $user->deliveryDetails()->find($deliveryDetailId);
        if (!$deliveryDetail) {
            throw new Exception("Delivery detail not found");
        }

        $country = $deliveryDetail->country;
        $state = $deliveryDetail->state;
        $city = $deliveryDetail->city;

        // Find a matching zone
        $zone = ShippingZone::where('country', $country)
            ->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', $state);
            })
            ->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', $city);
            })
            ->first();

        $rate = ShippingRate::with('method')
            ->where('shipping_method_id', $shippingMethodId)
            ->where('shipping_zone_id', $zone->id)
            ->first();

        foreach ($cartItems as $cartItem) {
            $order = $user->orders()->create([
                'store_id' => $cartItem->store_id,
                'product_id' => $cartItem->product_id,
                'product_variation_id' => $cartItem->product_variation_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->productVariation->price,
                'delivery_detail_id' => $deliveryDetailId,
                'shipping_method_id' => $shippingMethodId,
                'shipping_cost' => $rate->cost,
                'total' => ($cartItem->quantity * $cartItem->productVariation->price) + $rate->cost
            ]);

            $this->createStatus($order, OrderStatusEnum::ORDER_PLACED, $user->id);

            // Remove cart item
            $cartItem->delete();
        }
    }

    public function getOrders(User $user)
    {
        $query = Order::with([
            'product.productVariations.productMedia',
            'productVariation.productMedia',
            'store',
            'latestStatus',
            'shippingMethod',
        ]);

        // Limit orders based on role
        if ($user->hasRole('seller')) {
            $query->whereHas('store', fn($q) => $q->where('user_id', $user->id));
        } elseif (!$user->hasAnyRole(['admin', 'agent'])) {
            $query->where('user_id', $user->id);
        }

        // Paginate all results
        $orders = $query->paginate(15);

        // Map each order to attach selected variation
        $orders->getCollection()->transform(function ($order) {
            if ($order->product && $order->productVariation) {
                $order->product->product_variation = $order->productVariation;
                unset($order->product->productVariations);
            }
            return $order;
        });

        return $orders->toArray();
    }

    public function getOrdersActivity(User $user)
    {
        $totalOrdersCount = $user->orders()->count();

        $receivedOrdersCount = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::DELIVERED,
                ]);
            })->count();

        $cancelledOrdersCount = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDER_DECLINED,
                    OrderStatusEnum::CANCELLED,
                    OrderStatusEnum::RETURNED,
                    OrderStatusEnum::REFUNDED,
                    OrderStatusEnum::FAILED,
                ]);
            })->count();

        $toReceiveOrdersCount = $user->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDER_PLACED,
                    OrderStatusEnum::ORDER_CONFIRMED,
                    OrderStatusEnum::PROCESSING,
                    OrderStatusEnum::SHIPPED,
                    OrderStatusEnum::OUT_FOR_DELIVERY
                ]);
            })->count();

        return [
            "total_orders_count" => $totalOrdersCount,
            "received_orders_count" => $receivedOrdersCount,
            "cancelled_orders_count" => $cancelledOrdersCount,
            "to_receive_orders_count" => $toReceiveOrdersCount,
            "category_breakdown" => $this->getCategoryBreakDown($user)
        ];
    }

    public function cancelOrder(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::ORDER_PLACED);
        return $this->createStatus($order, OrderStatusEnum::CANCELLED, $userId);
    }

    public function requestRefund(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::DELIVERED);
        return $this->createStatus($order, OrderStatusEnum::REFUND_REQUESTED, $userId);
    }

    //SELLER
    public function acceptOrder(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::ORDER_PLACED);
        return $this->createStatus($order, OrderStatusEnum::ORDER_CONFIRMED, $userId);
    }

    //SELLER
    public function declineOrder(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::ORDER_PLACED);
        return $this->createStatus($order, OrderStatusEnum::ORDER_DECLINED, $userId);
    }

    //ADMIN
    public function processOrder(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::ORDER_CONFIRMED);
        return $this->createStatus($order, OrderStatusEnum::PROCESSING, $userId);
    }

    public function shipOrder(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::PROCESSING);
        return $this->createStatus($order, OrderStatusEnum::SHIPPED, $userId);
    }

    public function outForDelivery(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::SHIPPED);
        return $this->createStatus($order, OrderStatusEnum::OUT_FOR_DELIVERY, $userId);
    }

    public function markAsDelivered(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::OUT_FOR_DELIVERY);
        return $this->createStatus($order, OrderStatusEnum::DELIVERED, $userId);
    }

    public function approveRefund(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::REFUND_REQUESTED);
        return $this->createStatus($order, OrderStatusEnum::REFUND_APPROVED, $userId);
    }

    public function declineRefund(Order $order, string $userId)
    {
        $this->validateStatus($order, OrderStatusEnum::REFUND_REQUESTED);
        return $this->createStatus($order, OrderStatusEnum::REFUND_DECLINED, $userId);
    }

    private function validateStatus(Order $order, string $requiredStatus)
    {
        if ($order->latestStatus->status != $requiredStatus) {
            throw new Exception("Invalid order status transition");
        }
    }

    private function getOrderStatusText($status)
    {
        switch ($status) {
            case OrderStatusEnum::ORDER_PLACED:
                return "Pending confirmation.";
            case OrderStatusEnum::ORDER_CONFIRMED:
                return "Your order is confirmed and will be processed shortly.";
            case OrderStatusEnum::PROCESSING:
                return "Your order is being prepared for shipment.";
            case OrderStatusEnum::SHIPPED:
                return "Your order is on the way.";
            case OrderStatusEnum::OUT_FOR_DELIVERY:
                return "Your order is out for delivery and will arrive soon.";
            case OrderStatusEnum::DELIVERED:
                return "Order successfully delivered.";
            case OrderStatusEnum::ORDER_DECLINED:
                return "Your order has been declined.";
            case OrderStatusEnum::CANCELLED:
                return "Your order has been cancelled.";
            case OrderStatusEnum::REFUND_REQUESTED:
                return "You have requested a refund. Awaiting approval.";
            case OrderStatusEnum::REFUND_APPROVED:
                return "Your refund has been approved and is being processed.";
            case OrderStatusEnum::REFUND_DECLINED:
                return "Your refund request was declined.";
            case OrderStatusEnum::REFUNDED:
                return "Refund successfully processed.";
            case OrderStatusEnum::FAILED:
                return "Order failed. Please try again.";
        }
    }

    private function createStatus(Order $order, string $status, string $userId)
    {
        return OrderStatus::create([
            'order_id' => $order->id,
            'status' => $status,
            'description' => $this->getOrderStatusText($status),
            'changed_by' => $userId,
        ]);
    }

    private function getCategoryBreakdown(User $user)
    {
        $stats = [];

        // Step 1: get all years the user has orders
        $years = $user->orders()
            ->select(DB::raw('YEAR(created_at) as year'))
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('year')
            ->toArray();

        foreach ($years as $year) {

            // Determine last month to show
            $lastMonth = ($year == now()->year)
                ? now()->month     // current month
                : 12;              // full year

            // Build months
            $months = [];
            for ($m = 1; $m <= $lastMonth; $m++) {
                $monthName = Carbon::create()->month($m)->format('F');
                $months[$monthName] = []; // categories will fill here
            }

            // Get all categories the user bought in this year
            $categories = $user->orders()
                ->whereYear('orders.created_at', $year)
                ->whereHas('product.category')
                ->join('products', 'orders.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->groupBy('categories.id', 'categories.name')
                ->pluck('categories.name', 'categories.id');

            // Initialize all category totals to 0 for each month
            foreach ($months as $monthName => $data) {
                foreach ($categories as $categoryId => $categoryName) {
                    $months[$monthName][$categoryName] = 0;
                }
            }

            // Fetch actual spending grouped by month + category
            $orderData = $user->orders()
                ->whereYear('orders.created_at', $year)
                ->whereHas('product.category')
                ->select(
                    DB::raw('MONTH(orders.created_at) as month'),
                    DB::raw('categories.name as category'),
                    DB::raw('SUM(orders.total) as total')
                )
                ->join('products', 'orders.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->groupBy('month', 'category')
                ->get();

            // Insert into the monthly breakdown
            foreach ($orderData as $row) {
                $monthName = Carbon::create()->month($row->month)->format('F');
                $months[$monthName][$row->category] = (float) $row->total;
            }

            // Save stats for this year
            $stats[$year] = $months;
        }

        return $stats;
    }

}

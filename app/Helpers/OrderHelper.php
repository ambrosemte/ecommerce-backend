<?php
namespace App\Helpers;

use App\Enums\OrderStatusEnum;

class OrderHelper
{
    public static function getOrderStatusText($status)
    {
        switch ($status) {
            case OrderStatusEnum::ORDERPLACED:
                return "Pending confirmation.";
            case OrderStatusEnum::ORDERCONFIRMED:
                return "Your order is confirmed and will be processed shortly.";
            case OrderStatusEnum::PROCESSING:
                return "Your order is being prepared for shipment.";
            case OrderStatusEnum::SHIPPED:
                return "Your order is on the way.";
            case OrderStatusEnum::OUTFORDELIVERY:
                return "Your order is out for delivery and will arrive soon.";
            case OrderStatusEnum::DELIVERED:
                return "Order successfully delivered.";
            case OrderStatusEnum::CANCELLED:
                return "Your order has been cancelled.";
            case OrderStatusEnum::RETURNED:
                return "Return request received. Awaiting pickup.";
            case OrderStatusEnum::REFUNDED:
                return "Refund successfully processed.";
            case OrderStatusEnum::FAILED:
                return "Order failed. Please try again.";
        }
    }
}

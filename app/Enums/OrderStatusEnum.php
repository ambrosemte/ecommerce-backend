<?php

namespace App\Enums;


enum OrderStatusEnum
{
    const ORDER_PLACED = "Order Placed";
    const ORDER_CONFIRMED = "Order Confirmed";
    const PROCESSING = "Processing";
    const SHIPPED = "Shipped";
    const OUT_FOR_DELIVERY = "Out for Delivery";
    const DELIVERED = "Delivered";
    const ORDER_DECLINED = "Order Declined";
    const CANCELLED = "Cancelled";
    const REFUND_REQUESTED = "Refund Requested";
    const REFUND_APPROVED = 'Refund Approved';
    const REFUND_DECLINED = 'Refund Declined';
    const RETURNED = "Returned";
    const REFUNDED = "Refunded";
    const FAILED = "Failed";
}

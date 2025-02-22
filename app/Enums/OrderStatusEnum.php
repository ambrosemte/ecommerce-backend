<?php

namespace App\Enums;


enum OrderStatusEnum
{
    const ORDERPLACED = "Order Placed";
    const ORDERCONFIRMED = "Order Confirmed";
    const PROCESSING = "Processing";
    const SHIPPED = "Shipped";
    const OUTFORDELIVERY = "Out for Delivery";
    const DELIVERED = "Delivered";
    const CANCELLED = "Cancelled";
    const RETURNED = "Returned";
    const REFUNDED = "Refunded";
    const FAILED = "Failed";
}

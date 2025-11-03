<?php
namespace App\Enums;

enum OrderCategoryEnum
{
    const TORECEIVE = "To Receive";
    const CANCELLED = "Cancelled / Refunded";
    const TOREVIEW = "To Review";
}

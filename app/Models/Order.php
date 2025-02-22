<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        "user_id",
        "quantity",
        "status",
        "delivery_details_id",
        "tracking_id",
        "total_price",
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->tracking_id = 'TRACK-' . strtoupper(Str::random(10));
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

}

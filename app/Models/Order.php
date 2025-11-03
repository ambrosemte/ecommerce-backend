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
        "delivery_details_id",
        "tracking_id",
        "store_id",
        "product_id",
        "product_variation_id",
        "quantity",
        "price",
        "progress_level",
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->tracking_id = 'TRACK-' . strtoupper(Str::random(10));
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function latestStatus()
    {
        return $this->hasOne(OrderStatus::class)->latestOfMany();
    }

}

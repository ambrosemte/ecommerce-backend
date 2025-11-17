<?php

namespace App\Models;

use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        "user_id",
        "quantity",
        "delivery_detail_id",
        "tracking_id",
        "store_id",
        "product_id",
        "product_variation_id",
        "quantity",
        "price",
        "progress_level",
        "shipping_method_id",
        "shipping_cost",
        "total",
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->tracking_id = 'TRACK-' . strtoupper(Str::random(10));
        });

        static::retrieved(function ($productVariation) {
            $productVariation->convertPrice();
        });
    }

    public function convertPrice()
    {
        // Get the currency from the user preference or fallback
        $userCurrency = auth('sanctum')->user()->preferred_currency ?? '';

        // Call your conversion service
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($this->price ?? 0, $userCurrency);

        // Store or return the converted data
        $this->converted_price = $conversion['amount'];
        $this->currency = $conversion['symbol'];

        return $this;
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

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_variation_id', 'product_variation_id');
    }

}

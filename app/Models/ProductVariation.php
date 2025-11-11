<?php

namespace App\Models;

use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasUuids;
    protected $fillable = [
        "price",
        "quantity",
        "discount",
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'double',
        ];
    }

    protected static function booted(): void
    {
        static::retrieved(function ($productVariation) {
            $productVariation->convertPrice();
        });
    }

    public function convertPrice(?string $currency = '')
    {
        // 1️⃣ Get the currency from the user preference, session, or fallback
        $userCurrency = $currency
            ?? auth('sanctum')->user()->preferred_currency;

        // 2️⃣ Call your conversion service
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($this->price, $userCurrency);

        // 3️⃣ Store or return the converted data
        $this->converted_price = $conversion['amount'];
        $this->currency = $conversion['currency'];


        $this->discounted_price = ($this->discount != null)
            ? round($this->price - (($this->discount * $this->price) / 100), 2)
            : $this->price;

        $conversion = $currencyService->convert($this->discounted_price, $currency);

        $this->converted_discounted_price = $conversion['amount'];

        return $this;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productMedia()
    {
        return $this->hasOne(ProductMedia::class);
    }

    public function productSpecifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'product_variation_id');
    }
}

<?php

namespace App\Models;

use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProductVariation extends Model
{
    use HasUuids;
    protected $fillable = [
        "price",
        "quantity",
        "discount",
    ];

    protected $appends = ['average_rating'];


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

    public function convertPrice()
    {
        // Get the currency from the user preference or fallback
        $userCurrency = auth('sanctum')->user()->preferred_currency ?? '';

        // Call your conversion service
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($this->price, $userCurrency);

        // Store or return the converted data
        $this->converted_price = $conversion['amount'];
        $this->currency = $conversion['symbol'];


        $this->discounted_price = ($this->discount != null)
            ? round($this->price - (($this->discount * $this->price) / 100), 2)
            : $this->price;

        $conversion = $currencyService->convert($this->discounted_price, $userCurrency);

        $this->converted_discounted_price = $conversion['amount'];

        return $this;
    }

    public function getAverageRatingAttribute()
    {
        $avg = $this->reviews()
            ->where('approved', true)
            ->avg('rating');

        return is_null($avg) ? 0 : number_format($avg, 1);
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}

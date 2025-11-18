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

    protected $appends = [
        'average_rating',
        'converted_price',
        'currency',
        'discounted_price',
        'converted_discounted_price'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'double',
        ];
    }

    /**
     * Accessor for converted_price
     */
    public function getConvertedPriceAttribute()
    {
        $userCurrency = auth('sanctum')->user()->preferred_currency ?? '';
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($this->price, $userCurrency);

        return $conversion['amount'];
    }

    /**
     * Accessor for currency
     */
    public function getCurrencyAttribute()
    {
        $userCurrency = auth('sanctum')->user()->preferred_currency ?? '';
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($this->price, $userCurrency);

        return $conversion['symbol'];
    }

    /**
     * Accessor for discounted_price
     */
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount != null) {
            return round($this->price - (($this->discount * $this->price) / 100), 2);
        }

        return $this->price;
    }

    /**
     * Accessor for converted_discounted_price
     */
    public function getConvertedDiscountedPriceAttribute()
    {
        $discountedPrice = $this->discounted_price;

        $userCurrency = auth('sanctum')->user()->preferred_currency ?? '';
        $currencyService = app(CurrencyConversionService::class);
        $conversion = $currencyService->convert($discountedPrice, $userCurrency);

        return $conversion['amount'];
    }

    /**
     * Accessor for average_rating
     */
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

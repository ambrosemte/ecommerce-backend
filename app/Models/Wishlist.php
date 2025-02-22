<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasUuids;

    protected $fillable = [
        "user_id",
        "product_id",
        "product_variation_id",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function productVariations()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

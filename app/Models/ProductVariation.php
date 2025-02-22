<?php

namespace App\Models;

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
}

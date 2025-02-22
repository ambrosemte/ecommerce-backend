<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    use HasUuids;

    protected $fillable = [
        "product_id",
        "product_variation_id",
        "specification_key_id",
        "specification_value"
    ];

    public function specificationKey()
    {
        return $this->belongsTo(SpecificationKey::class);
    }
}

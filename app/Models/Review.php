<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_variation_id',
        'title',
        'message',
        'rating',
        'approved',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}

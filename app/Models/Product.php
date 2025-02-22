<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'user_id'
    ];

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function productVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    public function store(){
        return $this->belongsTo(Store::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'country',
        'state',
        'city',
        'is_active',
    ];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }

}

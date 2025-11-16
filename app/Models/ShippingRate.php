<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'shipping_zone_id',
        'cost',
        'days_min',
        'days_max'
    ];

    public function method()
    {
        return $this->belongsTo(ShippingMethod::class,'shipping_method_id');
    }

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class,'shipping_zone_id');
    }

}

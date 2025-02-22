<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDetail extends Model
{

    protected $fillable = [
        "contact_name",
        "street_address",
        "city",
        "state",
        "country",
        "zip_code",
        "phone",
        "alternative_phone",
        "note",
        "is_default",
    ];

    protected $casts = [
        "is_default" => "boolean",
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SpecificationValue extends Model
{
    use HasUuids;

    protected $fillable = [
        "value",
        "extra_info"
    ];
}

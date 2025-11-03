<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorySpecification extends Model
{
    protected $table = "category_specification";
    protected $fillable = [
        "category_id",
        "specification_key_id"
    ];
}

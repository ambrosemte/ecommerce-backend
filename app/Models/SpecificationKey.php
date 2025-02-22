<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SpecificationKey extends Model
{
    use HasUuids;

    protected $fillable = [
        "name",
        "type"
    ];

    public function specificationValues()
    {
        return $this->hasMany(SpecificationValue::class);
    }

}

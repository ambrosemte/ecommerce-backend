<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasUuids;

    protected $fillable = [
        "name",
        "description",
        "image_url",
        "is_active",
    ];

     public function getImageUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
    }

    public function specificationKeys()
    {
        return $this->belongsToMany(
            SpecificationKey::class,
            'category_specification', // pivot table name
            'category_id',                // foreign key on pivot for Category
            'specification_key_id'        // foreign key on pivot for SpecificationKey
        );
    }

}

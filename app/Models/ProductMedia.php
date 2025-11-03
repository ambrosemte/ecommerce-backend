<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    use HasUuids;

    protected $fillable = [
        'media_type',
        'media_url',
        'featured_media_url',
        'product_id'
    ];
    protected $table = 'product_medias';

    protected $casts = [
        "media_url" => "array"
    ];

    protected $hidden = [
        "created_at",
        "updated_at",
    ];

    public function getFeaturedMediaUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
    }


    public function getMediaUrlAttribute($value)
    {
        // Decode the JSON string into an array
        $mediaFiles = json_decode($value, true);

        // If the first decode results in a string, decode again
        if (is_string($mediaFiles)) {
            $mediaFiles = json_decode($mediaFiles, true);
        }

        // Check if the array is valid and contains at least one item
        if (is_array($mediaFiles) && count($mediaFiles) > 0) {
            // Map each media file path to its full URL
            return array_map(function ($filePath) {
                return url(Storage::url($filePath));
            }, $mediaFiles);
        }
    }

}

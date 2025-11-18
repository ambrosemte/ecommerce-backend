<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    use HasUuids;

    protected $fillable = [
        'store_id',
        'product_id',
        'media_url',
        'type',
        'caption',
        'expires_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    public function getMediaUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

   public function product(): BelongsTo
{
    return $this->belongsTo(Product::class);
}

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}

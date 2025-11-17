<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasUuids;
    protected $fillable = [
        'name',
        'image_url',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['total_sold', 'average_rating'];

    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
    }

    public function getTotalSoldAttribute()
    {
        return $this->orders()
            ->whereHas('latestStatus', function ($query) {
                $query->whereIn('status', [
                    OrderStatusEnum::ORDER_PLACED,
                    OrderStatusEnum::ORDER_CONFIRMED,
                    OrderStatusEnum::PROCESSING,
                    OrderStatusEnum::SHIPPED,
                    OrderStatusEnum::OUT_FOR_DELIVERY,
                    OrderStatusEnum::DELIVERED,
                ]);
            })->count();
    }

    public function getAverageRatingAttribute()
    {
        // Get all product variation IDs for this store
        $variationIds = $this->products()
            ->with('productVariations')
            ->get()
            ->pluck('productVariations.*.id') // nested collection of ids
            ->flatten(); // flatten to 1D collection

        // Calculate average rating across all reviews of these variations
        $avg = Review::whereIn('product_variation_id', $variationIds)
            ->where('approved', true)
            ->avg('rating');

        return is_null($avg) ? 0 : number_format($avg, 1);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function followers()
    {
        return $this->hasMany(StoreFollower::class);
    }

    public function following()
    {
        return $this->belongsToMany(Store::class, 'store_followers', 'store_id', 'user_id');
    }

    public function follow(User $user)
    {
        return $this->following()->attach($user);
    }

    public function unfollow(User $user)
    {
        return $this->following()->detach($user);
    }

    // Check if the user is following store
    public function isFollowing(User $user)
    {
        return $this->following()->where('followed_id', $user->id)->exists();
    }

}

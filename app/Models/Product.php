<?php

namespace App\Models;

use App\Enums\SessionKey;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'category_id'
    ];

    protected static function booted(): void
    {
        static::retrieved(function ($product) {
            $product->checkIfWishlisted();
        });
    }

    /**
     * Compute and attach the wished_list flag.
     */
    public function checkIfWishlisted(): void
    {
        $userId = auth('sanctum')->id();
        $user = User::find($userId);

        if ($user) {
            // Authenticated user
            $isWished = $user->wishlists()
                ->where('product_id', $this->id)
                ->exists();

            $this->wished_list = $isWished;
            return;
        }

        // Guest user
        $guestId = request()->input('guest_id');

        if (!$guestId) {
            $this->is_wishlisted = false;
            return;
        }

        $sessionKey = SessionKey::Wishlist->format($guestId);
        $ids = Cache::get($sessionKey, []);

        $productIds = collect($ids)->pluck('product_id')->toArray();

        $this->wished_list = in_array($this->id, $productIds, true);
    }


    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function productVariations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

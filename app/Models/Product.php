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
        'category_id',
        'store_id',
    ];
    
    protected $appends = ['wished_list'];

    /**
     * Accessor for wished_list
     */
    public function getWishedListAttribute(): bool
    {
        $userId = auth('sanctum')->id();
        $user = User::find($userId);

        if ($user) {
            // Authenticated user
            return $user->wishlists()
                ->where('product_id', $this->id)
                ->exists();
        }

        // Guest user
        $guestId = request()->input('guest_id');

        if (!$guestId) {
            return false;
        }

        $sessionKey = SessionKey::Wishlist->format($guestId);
        $ids = Cache::get($sessionKey, []);

        $productIds = collect($ids)->pluck('product_id')->toArray();

        return in_array($this->id, $productIds, true);
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

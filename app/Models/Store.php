<?php

namespace App\Models;

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

    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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

    // Check if the user is following another user
    public function isFollowing(User $user)
    {
        return $this->following()->where('followed_id', $user->id)->exists();
    }

}

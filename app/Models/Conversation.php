<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasUuids;
    protected $fillable = ['user_one_id', 'user_two_id', 'is_completed'];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function agents()
    {
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}

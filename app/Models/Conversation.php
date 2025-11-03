<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasUuids;
    protected $fillable = ['user_one_id', 'user_two_id', 'is_completed'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'bool',
        ];
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_user');
    }
}

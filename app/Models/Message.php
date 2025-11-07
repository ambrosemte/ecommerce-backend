<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasUuids;

    protected $fillable = [
        'sender_id',
        'message',
        'is_read',
        'conversation_id',
        'is_user_message'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_user_message' => 'boolean'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    protected $appends = ['sender_role'];

    public function getSenderRoleAttribute()
    {
        if (!$this->sender) {
            return null;
        }

        return $this->sender->getRoleNames()->first();
    }
}

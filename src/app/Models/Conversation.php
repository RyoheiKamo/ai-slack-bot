<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'thread_ts',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ConversationMessage::class)
            ->latestOfMany('message_created_at');
    }

    public function firstMessage()
    {
        return $this->hasOne(ConversationMessage::class)
            ->oldestOfMany('message_created_at');
    }
}

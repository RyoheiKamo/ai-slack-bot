<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'slack_user_id',
        'message_id',
        'role',
        'content',
        'message_created_at',
    ];

    protected $casts = [
        'message_created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            Conversation::class
        );
    }

    public function slackUser(): BelongsTo
    {
        return $this->belongsTo(
            SlackUser::class
        );
    }
}

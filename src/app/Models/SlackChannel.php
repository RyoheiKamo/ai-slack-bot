<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'slack_channel_id',
        'name',
        'first_used_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'first_used_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}

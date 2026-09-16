<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'slack_user_id',
        'display_name',
        'real_name',
        'first_used_at',
        'last_used_at',
    ];

    protected $casts = [
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];
}

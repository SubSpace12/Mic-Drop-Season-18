<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordAccessToken extends Model
{
    protected $table = 'discord_access_tokens';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'expires_at',
        'scope',
        'user_id',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',  // CRITICAL
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

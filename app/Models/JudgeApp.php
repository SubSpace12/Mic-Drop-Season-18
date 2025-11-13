<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JudgeApp extends Model
{
    protected $table = 'apps';
    protected $fillable = [
        'user_id',
        'fav_artists',
        'least_fav_artists',
        'fav_genres',
        'least_fav_genres',
        'judging_style',
        'safe_pick_criteria',
        'bonus',
        'banned_artists',
        'longer',
    ];

    protected $casts = [
        'user-id' => 'string',
        'bonus' => 'boolean',
        'longer' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

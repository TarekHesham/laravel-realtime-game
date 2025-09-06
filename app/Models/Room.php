<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $fillable = [
        'code',
        'board',
        'status',
        'current_turn',
        'winner',
        'is_draw'
    ];

    protected $casts = [
        'board' => 'array',
        'is_draw' => 'boolean',
    ];

    public function players()
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function activePlayers()
    {
        return $this->players()->where('is_spectator', false);
    }

    public function spectators()
    {
        return $this->players()->where('is_spectator', true);
    }

    public static function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getPlayerBySession($sessionId)
    {
        return $this->players()->where('session_id', $sessionId)->first();
    }

    public function canJoinAsPlayer()
    {
        return $this->activePlayers()->count() < 2;
    }

    public function hasDisconnectedPlayers()
    {
        return $this->activePlayers()
            ->where('last_active', '<', now()->subMinutes(5))
            ->exists();
    }
}

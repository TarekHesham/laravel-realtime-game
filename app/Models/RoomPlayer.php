<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomPlayer extends Model
{
    protected $fillable = [
        'room_id',
        'session_id',
        'name',
        'symbol',
        'is_spectator',
        'last_active'
    ];

    protected $casts = [
        'is_spectator' => 'boolean',
        'last_active' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}

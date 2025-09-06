<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $symbol, $playersCount;

    public function __construct($symbol, $playersCount)
    {
        $this->symbol = $symbol;
        $this->playersCount = $playersCount;
    }

    public function broadcastOn()
    {
        return new Channel('game');
    }

    public function broadcastAs()
    {
        return 'player.joined';
    }
}

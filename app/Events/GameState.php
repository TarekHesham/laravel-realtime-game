<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameState implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public $gameState,
        public $winner,
        public $isDraw,
        public $currentTurn
    ) {}

    public function broadcastOn()
    {
        return new Channel('game');
    }

    public function broadcastAs()
    {
        return 'game.state';
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameMove implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $x, $y, $symbol, $nextTurn, $board;

    public function __construct($x, $y, $symbol, $nextTurn, $board)
    {
        $this->x = $x;
        $this->y = $y;
        $this->symbol = $symbol;
        $this->nextTurn = $nextTurn;
        $this->board = $board;
    }

    public function broadcastOn()
    {
        return new Channel('game');
    }

    public function broadcastAs()
    {
        return 'game.move';
    }
}

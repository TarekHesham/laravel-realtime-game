<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameWin implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $x, $y, $symbol, $winner, $isDraw, $board;

    public function __construct($x, $y, $symbol, $winner, $isDraw, $board)
    {
        $this->x = $x;
        $this->y = $y;
        $this->symbol = $symbol;
        $this->winner = $winner;
        $this->isDraw = $isDraw;
        $this->board = $board;
    }

    public function broadcastOn()
    {
        return new Channel('game');
    }

    public function broadcastAs()
    {
        return 'game.win';
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GameMove implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomCode;
    public $x;
    public $y;
    public $symbol;
    public $nextTurn;
    public $board;

    public function __construct($roomCode, $x, $y, $symbol, $nextTurn, $board)
    {
        $this->roomCode = $roomCode;
        $this->x = $x;
        $this->y = $y;
        $this->symbol = $symbol;
        $this->nextTurn = $nextTurn;
        $this->board = $board;
    }

    public function broadcastOn()
    {
        return new Channel('game.' . $this->roomCode);
    }

    public function broadcastAs()
    {
        return 'game.move';
    }
}

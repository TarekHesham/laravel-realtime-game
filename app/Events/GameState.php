<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class GameState implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomCode;
    public $gameState;
    public $winner;
    public $isDraw;
    public $currentTurn;
    public $spectators;

    public function __construct($roomCode, $gameState, $winner, $isDraw, $currentTurn, $spectators)
    {
        $this->roomCode = $roomCode;
        $this->gameState = $gameState;
        $this->winner = $winner;
        $this->isDraw = $isDraw;
        $this->currentTurn = $currentTurn;
        $this->spectators = $spectators;
    }

    public function broadcastOn()
    {
        return new Channel('game.' . $this->roomCode);
    }

    public function broadcastAs()
    {
        return 'game.state';
    }
}

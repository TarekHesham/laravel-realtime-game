<?php

namespace App\Http\Controllers;

use App\Events\GameMove;
use App\Events\GameReset;
use App\Events\GameState;
use App\Events\GameWin;
use App\Models\Room;
use App\Models\RoomPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    public function createRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'الاسم مطلوب'], 400);
        }

        $room = Room::create([
            'code' => Room::generateUniqueCode(),
            'board' => array_fill(0, 3, array_fill(0, 3, null)),
            'status' => 'waiting',
            'current_turn' => 'X'
        ]);

        RoomPlayer::create([
            'room_id' => $room->id,
            'session_id' => session()->getId(),
            'name' => $request->name,
            'is_spectator' => false
        ]);

        return response()->json([
            'room_code' => $room->code,
            'redirect_url' => '/room?join=' . $room->code
        ]);
    }

    public function joinRoom(Request $request, $roomCode)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'الاسم مطلوب'], 400);
        }

        $room = Room::where('code', $roomCode)->first();
        if (!$room) {
            return response()->json(['error' => 'الغرفة غير موجودة'], 404);
        }

        $existingPlayer = $room->getPlayerBySession(session()->getId());
        if ($existingPlayer) {
            return response()->json(['message' => 'انت موجود بالفعل في الغرفة']);
        }

        $canJoinAsPlayer = $room->canJoinAsPlayer();

        RoomPlayer::create([
            'room_id' => $room->id,
            'session_id' => session()->getId(),
            'name' => $request->name,
            'is_spectator' => !$canJoinAsPlayer
        ]);

        $room->spectators()->where('last_active', '<', now()->subSeconds(15))->delete();
        $spectators = $room->spectators()->get();
        broadcast(new GameState($room->code, 'playing', null, false, null, $spectators));

        return response()->json(['message' => 'تم الانضمام بنجاح']);
    }

    public function getGameState($roomCode)
    {
        $room = Room::where('code', $roomCode)->with('players')->first();
        if (!$room) {
            return response()->json(['error' => 'الغرفة غير موجودة'], 404);
        }

        $player = $room->getPlayerBySession(session()->getId());
        if ($player) {
            $player->update(['last_active' => now()]);
        }

        $room->spectators()->where('last_active', '<', now()->subSeconds(15))->delete();
        $spectators = $room->spectators()->get();
        if ($room->hasDisconnectedPlayers()) {
            broadcast(new GameState($room->code, 'finished', null, false, null, $spectators));

            $room->players()->delete();
            $room->delete();

            return response()->json(['error' => 'تم إغلاق الغرفة بسبب خروج لاعب'], 410);
        }

        $activePlayers = $room->activePlayers()->get();

        return response()->json([
            'room_code' => $room->code,
            'board' => $room->board,
            'status' => $room->status,
            'current_turn' => $room->current_turn,
            'winner' => $room->winner,
            'is_draw' => $room->is_draw,
            'player' => $player,
            'players' => $activePlayers->map(function ($p) {
                return [
                    'name'   => $p->name,
                    'symbol' => $p->symbol,
                    'score'  => $p->score,
                ];
            }),
            'spectators' => $room->spectators()->get(),
            'can_choose_symbol' => $player && !$player->is_spectator && $player->symbol == null,
            'available_symbols' => $this->getAvailableSymbols($room)
        ]);
    }

    public function setSymbol(Request $request, $roomCode)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|in:X,O'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'رمز غير صحيح'], 400);
        }

        $room = Room::where('code', $roomCode)->first();
        if (!$room) {
            return response()->json(['error' => 'الغرفة غير موجودة'], 404);
        }

        $player = $room->getPlayerBySession(session()->getId());
        if (!$player || $player->is_spectator) {
            return response()->json(['error' => 'غير مسموح لك اختيار رمز'], 400);
        }

        if ($player->symbol) {
            return response()->json(['error' => 'لديك رمز بالفعل'], 400);
        }

        $symbolTaken = $room->activePlayers()->where('symbol', $request->symbol)->exists();
        if ($symbolTaken) {
            return response()->json(['error' => 'الرمز محجوز بالفعل'], 400);
        }

        $player->update(['symbol' => $request->symbol]);

        // Check if we can start the game
        $playersWithSymbols = $room->activePlayers()->whereNotNull('symbol')->count();
        $spectators = $room->spectators()->get();
        if ($playersWithSymbols === 2) {
            $room->update(['status' => 'playing']);
            broadcast(new GameState($room->code, 'playing', null, false, $room->current_turn, $spectators));
        }

        return response()->json(['symbol' => $request->symbol]);
    }

    public function makeMove(Request $request, $roomCode)
    {
        $validator = Validator::make($request->all(), [
            'x' => 'required|integer|min:0|max:2',
            'y' => 'required|integer|min:0|max:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'إحداثيات غير صحيحة'], 400);
        }

        $room = Room::where('code', $roomCode)->first();
        if (!$room) {
            return response()->json(['error' => 'الغرفة غير موجودة'], 404);
        }

        $player = $room->getPlayerBySession(session()->getId());
        if (!$player || $player->is_spectator) {
            return response()->json(['error' => 'غير مسموح لك اللعب'], 400);
        }

        if ($room->status !== 'playing') {
            return response()->json(['error' => 'اللعبة لم تبدأ بعد'], 400);
        }

        if ($player->symbol !== $room->current_turn) {
            return response()->json(['error' => 'مش دورك دلوقتي'], 400);
        }

        $x = $request->get('x');
        $y = $request->get('y');
        $board = $room->board;

        if ($board[$x][$y] !== null) {
            return response()->json(['error' => 'المربع محجوز بالفعل'], 400);
        }

        $board[$x][$y] = $player->symbol;

        $winnerSymbol = $this->checkWinner($board);

        if ($winnerSymbol) {
            $winnerPlayer = $room->activePlayers()->where('symbol', $winnerSymbol)->first();
            $winnerName = $winnerPlayer ? $winnerPlayer->name : null;

            if ($winnerPlayer) {
                $winnerPlayer->increment('score');
            }

            $room->update([
                'board'   => $board,
                'status'  => 'finished',
                'winner'  => $winnerName,
                'is_draw' => false
            ]);

            broadcast(new GameWin($room->code, $x, $y, $player->symbol, $winnerName, false, $board));
        } elseif ($this->checkDraw($board)) {
            $room->update([
                'board'   => $board,
                'status'  => 'finished',
                'winner'  => null,
                'is_draw' => true
            ]);

            broadcast(new GameWin($room->code, $x, $y, $player->symbol, null, true, $board));
        } else {
            $nextTurn = $player->symbol === 'X' ? 'O' : 'X';

            $room->update([
                'board'        => $board,
                'current_turn' => $nextTurn
            ]);

            broadcast(new GameMove($room->code, $x, $y, $player->symbol, $nextTurn, $board));
        }

        return response()->json(['status' => 'ok']);
    }

    public function resetGame($roomCode)
    {
        $room = Room::where('code', $roomCode)->first();
        if (!$room) {
            return response()->json(['error' => 'الغرفة غير موجودة'], 404);
        }

        if ($room->status !== 'finished') {
            return response()->json(['message' => 'اللعبة قيد التقدم'], 200);
        }

        $winnerSymbol = $room->activePlayers()
            ->where('name', $room->winner)
            ->value('symbol') ?: 'X';

        $room->update([
            'board'        => array_fill(0, 3, array_fill(0, 3, null)),
            'status'       => 'playing',
            'current_turn' => $winnerSymbol,
            'winner'       => null,
            'is_draw'      => false
        ]);

        broadcast(new GameReset($room->code));

        return response()->json(['status' => 'reset']);
    }

    private function getAvailableSymbols($room)
    {
        $takenSymbols = $room->activePlayers()->whereNotNull('symbol')->pluck('symbol')->toArray();
        return array_diff(['X', 'O'], $takenSymbols);
    }

    private function checkWinner($board)
    {
        // Check rows
        for ($i = 0; $i < 3; $i++) {
            if (
                $board[$i][0] !== null &&
                $board[$i][0] === $board[$i][1] &&
                $board[$i][1] === $board[$i][2]
            ) {
                return $board[$i][0];
            }
        }

        // Check columns
        for ($j = 0; $j < 3; $j++) {
            if (
                $board[0][$j] !== null &&
                $board[0][$j] === $board[1][$j] &&
                $board[1][$j] === $board[2][$j]
            ) {
                return $board[0][$j];
            }
        }

        // Check diagonals
        if (
            $board[0][0] !== null &&
            $board[0][0] === $board[1][1] &&
            $board[1][1] === $board[2][2]
        ) {
            return $board[0][0];
        }

        if (
            $board[0][2] !== null &&
            $board[0][2] === $board[1][1] &&
            $board[1][1] === $board[2][0]
        ) {
            return $board[0][2];
        }

        return null;
    }

    private function checkDraw($board)
    {
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell === null) {
                    return false;
                }
            }
        }
        return true;
    }
}

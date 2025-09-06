<?php

namespace App\Http\Controllers;

use App\Events\GameMove;
use App\Events\GameReset;
use App\Events\GameState;
use App\Events\GameWin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    private const CACHE_TTL = 3600;
    private const BOARD_SIZE = 3;

    public function getSymbol()
    {
        $chosenSymbols = cache()->get('chosen_symbols', []);
        $currentTurn = cache()->get('currentTurn', 'X');
        $gameState = cache()->get('game_state', 'waiting'); // waiting, playing, finished
        $winner = cache()->get('winner', null);
        $isDraw = cache()->get('isDraw', false);

        if (session()->has('symbol')) {
            $playerSymbol = session('symbol');
            $chosenSymbols = cache()->get('chosen_symbols', []);

            if (!in_array($playerSymbol, $chosenSymbols)) {
                $chosenSymbols[] = $playerSymbol;
                cache()->put('chosen_symbols', $chosenSymbols, now()->addSeconds(self::CACHE_TTL));

                if (count($chosenSymbols) === 2) {
                    cache()->put('game_state', 'playing', now()->addSeconds(self::CACHE_TTL));
                }
            }

            $board = cache()->get('board', array_fill(0, 3, array_fill(0, 3, null)));


            return response()->json([
                'symbol' => $playerSymbol,
                'choose' => false,
                'currentTurn' => cache()->get('currentTurn', 'X'),
                'gameState' => cache()->get('game_state', 'waiting'),
                'board' => $board,
                'spectator' => cache()->get('spectator', false),
                'isDraw' => $isDraw,
                'winner' => $winner
            ]);
        }

        if (empty($chosenSymbols)) {
            return response()->json([
                'symbol' => null,
                'choose' => true,
                'currentTurn' => $currentTurn,
                'gameState' => $gameState,
                'board' => cache()->get('board', array_fill(0, 3, array_fill(0, 3, null)))
            ]);
        }

        if (count($chosenSymbols) === 1) {
            $availableSymbol = in_array('X', $chosenSymbols) ? 'O' : 'X';
            session(['symbol' => $availableSymbol]);
            cache()->put('chosen_symbols', array_merge($chosenSymbols, [$availableSymbol]), now()->addSeconds(self::CACHE_TTL));

            cache()->put('game_state', 'playing', now()->addSeconds(self::CACHE_TTL));

            $this->getGameState();

            return response()->json([
                'symbol' => $availableSymbol,
                'choose' => false,
                'currentTurn' => $currentTurn,
                'gameState' => 'playing',
                'board' => cache()->get('board', array_fill(0, 3, array_fill(0, 3, null)))
            ]);
        }

        return response()->json([
            'symbol' => null,
            'choose' => false,
            'currentTurn' => $currentTurn,
            'gameState' => $gameState,
            'board' => cache()->get('board', array_fill(0, 3, array_fill(0, 3, null))),
            'spectator' => true,
            'isDraw' => $isDraw,
            'winner' => $winner
        ]);
    }

    public function setSymbol(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|in:X,O'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'رمز غير صحيح'], 400);
        }

        $symbol = $request->symbol;
        $chosenSymbols = cache()->get('chosen_symbols', []);

        if (in_array($symbol, $chosenSymbols)) {
            return response()->json(['error' => 'الرمز محجوز بالفعل'], 400);
        }

        if (count($chosenSymbols) >= 2) {
            return response()->json(['error' => 'اللعبة ممتلئة'], 400);
        }

        session(['symbol' => $symbol]);
        $chosenSymbols[] = $symbol;
        cache()->put('chosen_symbols', $chosenSymbols, now()->addSeconds(self::CACHE_TTL));

        if (count($chosenSymbols) === 1) {
            cache()->put('board', array_fill(0, 3, array_fill(0, 3, null)), now()->addSeconds(self::CACHE_TTL));
            cache()->put('currentTurn', 'X', now()->addSeconds(self::CACHE_TTL));
            cache()->put('game_state', 'waiting', now()->addSeconds(self::CACHE_TTL));
        }

        if (count($chosenSymbols) === 2) {
            cache()->put('game_state', 'playing', now()->addSeconds(self::CACHE_TTL));
            $this->getGameState();
        }


        return response()->json(['symbol' => $symbol, 'status' => 'success']);
    }

    public function move(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'x' => 'required|integer|min:0|max:2',
            'y' => 'required|integer|min:0|max:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'إحداثيات غير صحيحة'], 400);
        }

        $x = $request->get('x');
        $y = $request->get('y');
        $symbol = session('symbol', 'X');
        $currentTurn = cache()->get('currentTurn');
        $gameState = cache()->get('game_state', 'waiting');
        $board = cache()->get('board', array_fill(0, 3, array_fill(0, 3, null)));

        if ($gameState !== 'playing') {
            return response()->json(['error' => 'اللعبة لم تبدأ بعد أو انتهت'], 400);
        }

        if (!in_array($symbol, ['X', 'O'])) {
            return response()->json(['error' => 'رمز غير متاح'], 400);
        }

        if ($symbol !== $currentTurn) {
            return response()->json(['error' => 'مش دورك دلوقتي'], 400);
        }

        if ($board[$x][$y] !== null) {
            return response()->json(['error' => 'المربع محجوز بالفعل'], 400);
        }

        $board[$x][$y] = $symbol;
        cache()->put('board', $board, now()->addSeconds(self::CACHE_TTL));

        $winner = $this->checkWinner($board);
        $isDraw = $this->checkDraw($board);

        if ($winner || $isDraw) {
            cache()->put('game_state', 'finished', now()->addSeconds(self::CACHE_TTL));
            cache()->put('winner', $winner, now()->addSeconds(self::CACHE_TTL));
            cache()->put('isDraw', $isDraw, now()->addSeconds(self::CACHE_TTL));

            broadcast(new GameWin($x, $y, $symbol, $winner, $isDraw, $board));

            return response()->json([
                'status' => 'finished',
                'symbol' => $symbol,
                'winner' => $winner,
                'isDraw' => $isDraw,
                'board' => $board
            ]);
        }

        $nextTurn = $symbol === 'X' ? 'O' : 'X';
        cache()->put('currentTurn', $nextTurn, now()->addSeconds(self::CACHE_TTL));

        broadcast(new GameMove($x, $y, $symbol, $nextTurn, $board));

        return response()->json([
            'status' => 'ok',
            'symbol' => $symbol,
            'nextTurn' => $nextTurn,
            'board' => $board
        ]);
    }

    public function reset()
    {
        session()->forget('symbol');
        cache()->forget('chosen_symbols');
        cache()->forget('currentTurn');
        cache()->forget('board');
        cache()->forget('game_state');

        broadcast(new GameReset());

        return response()->json(['status' => 'reset']);
    }

    private function getGameState()
    {
        $board = cache()->get('board', array_fill(0, 3, array_fill(0, 3, null)));
        $winner = $this->checkWinner($board);
        $isDraw = $this->checkDraw($board);

        broadcast(new GameState(
            cache()->get('game_state', 'waiting'),
            $winner,
            $isDraw,
            cache()->get('currentTurn'),
        ));
    }

    private function checkWinner($board)
    {
        for ($i = 0; $i < self::BOARD_SIZE; $i++) {
            if (
                $board[$i][0] !== null &&
                $board[$i][0] === $board[$i][1] &&
                $board[$i][1] === $board[$i][2]
            ) {
                return $board[$i][0];
            }
        }

        for ($j = 0; $j < self::BOARD_SIZE; $j++) {
            if (
                $board[0][$j] !== null &&
                $board[0][$j] === $board[1][$j] &&
                $board[1][$j] === $board[2][$j]
            ) {
                return $board[0][$j];
            }
        }

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

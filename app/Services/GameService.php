<?php

namespace App\Services;

class GameService
{
    private const CACHE_PREFIX = 'game:';
    private const CACHE_TTL = 3600;

    public function getBoard(): array
    {
        return cache()->get(
            self::CACHE_PREFIX . 'board',
            array_fill(0, 3, array_fill(0, 3, null))
        );
    }

    public function setBoard(array $board): void
    {
        cache()->put(self::CACHE_PREFIX . 'board', $board, now()->addSeconds(self::CACHE_TTL));
    }

    public function getCurrentTurn(): string
    {
        return cache()->get(self::CACHE_PREFIX . 'current_turn', 'X');
    }

    public function setCurrentTurn(string $turn): void
    {
        cache()->put(self::CACHE_PREFIX . 'current_turn', $turn, now()->addSeconds(self::CACHE_TTL));
    }

    public function getChosenSymbols(): array
    {
        return cache()->get(self::CACHE_PREFIX . 'chosen_symbols', []);
    }

    public function addChosenSymbol(string $symbol): void
    {
        $symbols = $this->getChosenSymbols();
        if (!in_array($symbol, $symbols)) {
            $symbols[] = $symbol;
            cache()->put(self::CACHE_PREFIX . 'chosen_symbols', $symbols, now()->addSeconds(self::CACHE_TTL));
        }
    }

    public function getGameState(): string
    {
        return cache()->get(self::CACHE_PREFIX . 'game_state', 'waiting');
    }

    public function setGameState(string $state): void
    {
        cache()->put(self::CACHE_PREFIX . 'game_state', $state, now()->addSeconds(self::CACHE_TTL));
    }

    public function resetGame(): void
    {
        cache()->forget(self::CACHE_PREFIX . 'board');
        cache()->forget(self::CACHE_PREFIX . 'current_turn');
        cache()->forget(self::CACHE_PREFIX . 'chosen_symbols');
        cache()->forget(self::CACHE_PREFIX . 'game_state');
    }

    public function isValidMove(int $x, int $y, array $board): bool
    {
        return isset($board[$x][$y]) && $board[$x][$y] === null;
    }

    public function checkWinner(array $board): ?string
    {
        for ($i = 0; $i < 3; $i++) {
            if (
                $board[$i][0] !== null &&
                $board[$i][0] === $board[$i][1] &&
                $board[$i][1] === $board[$i][2]
            ) {
                return $board[$i][0];
            }
        }

        for ($j = 0; $j < 3; $j++) {
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

    public function isDraw(array $board): bool
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

    public function getWinningLine(array $board): ?array
    {
        for ($i = 0; $i < 3; $i++) {
            if (
                $board[$i][0] !== null &&
                $board[$i][0] === $board[$i][1] &&
                $board[$i][1] === $board[$i][2]
            ) {
                return [
                    'type' => 'row',
                    'index' => $i,
                    'positions' => [[$i, 0], [$i, 1], [$i, 2]]
                ];
            }
        }

        for ($j = 0; $j < 3; $j++) {
            if (
                $board[0][$j] !== null &&
                $board[0][$j] === $board[1][$j] &&
                $board[1][$j] === $board[2][$j]
            ) {
                return [
                    'type' => 'column',
                    'index' => $j,
                    'positions' => [[0, $j], [1, $j], [2, $j]]
                ];
            }
        }

        if (
            $board[0][0] !== null &&
            $board[0][0] === $board[1][1] &&
            $board[1][1] === $board[2][2]
        ) {
            return [
                'type' => 'diagonal',
                'index' => 0,
                'positions' => [[0, 0], [1, 1], [2, 2]]
            ];
        }

        if (
            $board[0][2] !== null &&
            $board[0][2] === $board[1][1] &&
            $board[1][1] === $board[2][0]
        ) {
            return [
                'type' => 'diagonal',
                'index' => 1,
                'positions' => [[0, 2], [1, 1], [2, 0]]
            ];
        }

        return null;
    }
}

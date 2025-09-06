<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::bind('game', function ($value) {
    return cache()->get("game:{$value}");
});

Route::view('/', 'game')->name('game');

Route::prefix('api/game')->group(function () {
    Route::get('symbol', [GameController::class, 'getSymbol'])->name('game.symbol.get');
    Route::post('symbol', [GameController::class, 'setSymbol'])->name('game.symbol.set');
    Route::post('move', [GameController::class, 'move'])->name('game.move');
    Route::post('reset', [GameController::class, 'reset'])->name('game.reset');
});

Route::get('spectate', function () {
    return view('game', ['spectatorMode' => true]);
})->name('game.spectate');

<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::bind('game', function ($value) {
    return cache()->get("game:{$value}");
});

// Main page
Route::get('/', function () {
    return view('home');
});

// Room page
Route::get('/room', function () {
    $roomCode = request('join');
    if (!$roomCode) {
        return redirect('/');
    }
    return view('game', compact('roomCode'));
});

// API Routes
Route::prefix('api/game')->group(function () {
    Route::post('/create-room', [GameController::class, 'createRoom']);
    Route::post('/join-room/{roomCode}', [GameController::class, 'joinRoom']);
    Route::get('/room/{roomCode}', [GameController::class, 'getGameState']);
    Route::post('/room/{roomCode}/symbol', [GameController::class, 'setSymbol']);
    Route::post('/room/{roomCode}/move', [GameController::class, 'makeMove']);
    Route::post('/room/{roomCode}/reset', [GameController::class, 'resetGame']);
});


Route::get('spectate', function () {
    return view('game', ['spectatorMode' => true]);
})->name('game.spectate');

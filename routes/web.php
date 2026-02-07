<?php

use App\Models\Game;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $recentGames = Game::query()
        ->where('user_id', auth()->id())
        ->latest()
        ->take(3)
        ->get()
        ->map(function (Game $game) {
            return [
                'id' => $game->id,
                'name' => $game->name,
                'players' => $game->players,
                'board' => $game->board,
                'created_at' => optional($game->created_at)->toIso8601String(),
            ];
        });

    return Inertia::render('Dashboard', [
        'recentGames' => $recentGames,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/games', [App\Http\Controllers\GameController::class, 'store'])->middleware('auth');

Route::get('/leaderboard', [App\Http\Controllers\PokerSessionController::class, 'leaderboard'])
    ->middleware(['auth', 'verified'])
    ->name('leaderboard');

Route::get('/sessions', [App\Http\Controllers\SessionGroupController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('sessions');

Route::post('/sessions', [App\Http\Controllers\SessionGroupController::class, 'store'])->middleware('auth');

Route::get('/sessions/{code}', [App\Http\Controllers\SessionGroupController::class, 'show'])
    ->middleware('auth')
    ->name('session-detail');

Route::post('/sessions/{code}/entry', [App\Http\Controllers\SessionGroupController::class, 'addEntry'])
    ->middleware('auth');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

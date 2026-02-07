<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'players' => 'required|array|min:2|max:9',
            'players.*' => 'string|regex:/^[A-KQJT2-9][shdc][A-KQJT2-9][shdc]$/',
            'board' => 'nullable|string|regex:/^[A-KQJT2-9shdc]*$/',
        ]);

        $game = Game::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'players' => $request->players,
            'board' => $request->board ?? '',
        ]);

        return response()->json($game, 201);
    }
}

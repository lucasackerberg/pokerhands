<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class OddsController extends Controller
{
    public function calculate(Request $request)
    {
        $request->validate([
            'players' => 'required|array|min:2|max:9',
            'players.*' => 'string|regex:/^[A-KQJT2-9][shdc][A-KQJT2-9][shdc]$/',
            'board' => 'nullable|string|regex:/^[A-KQJT2-9shdc]*$/',
        ]);

        $players = $request->input('players');
        $board = $request->input('board', '');

        $input = json_encode(['players' => $players, 'board' => $board]);

        $scriptPath = base_path('scripts/calculate-odds.js');

        $result = Process::input($input)->run('"C:\Program Files\nodejs\node.exe" "' . $scriptPath . '"');

        Log::info('Process result: ' . $result->output());
        Log::info('Process error: ' . $result->errorOutput());

        if ($result->failed()) {
            return response()->json(['error' => 'Calculation failed'], 500);
        }

        $output = json_decode($result->output(), true);

        if (isset($output['error'])) {
            return response()->json(['error' => $output['error']], 400);
        }

        return response()->json($output);
    }
}

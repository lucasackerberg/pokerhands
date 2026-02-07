<?php

namespace App\Http\Controllers;

use App\Models\PokerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PokerSessionController extends Controller
{
    public function leaderboard()
    {
        // Get stats only from session group entries
        $leaderboard = DB::table('session_group_entries')
            ->join('users', 'session_group_entries.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(cash_out - buy_in) as total_profit'),
                DB::raw('SUM(buy_in) as total_buy_in'),
                DB::raw('SUM(cash_out) as total_cash_out'),
                DB::raw('COUNT(*) as sessions_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_profit')
            ->get();

        // Get user's recent sessions from group entries
        $userSessions = DB::table('session_group_entries')
            ->join('session_groups', 'session_group_entries.session_group_id', '=', 'session_groups.id')
            ->where('session_group_entries.user_id', auth()->id())
            ->select(
                'session_group_entries.id',
                'session_group_entries.buy_in',
                'session_group_entries.cash_out',
                'session_group_entries.notes',
                'session_groups.session_date',
                'session_groups.name as session_name'
            )
            ->orderByDesc('session_groups.session_date')
            ->limit(10)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'buy_in' => $entry->buy_in,
                    'cash_out' => $entry->cash_out,
                    'profit' => $entry->cash_out - $entry->buy_in,
                    'session_date' => $entry->session_date,
                    'notes' => $entry->notes,
                    'session_name' => $entry->session_name,
                ];
            });

        return Inertia::render('Leaderboard', [
            'leaderboard' => $leaderboard,
            'userSessions' => $userSessions,
        ]);
    }
}

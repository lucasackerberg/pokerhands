<?php

namespace App\Http\Controllers;

use App\Models\SessionGroup;
use App\Models\SessionGroupEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SessionGroupController extends Controller
{
    public function index()
    {
        $sessions = SessionGroup::where('organizer_id', auth()->id())
            ->orWhereHas('entries', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['organizer', 'entries.user'])
            ->latest()
            ->get();

        return Inertia::render('Sessions', [
            'sessions' => $sessions,
            'canOrganize' => auth()->user()->can_organize,
        ]);
    }

    public function show($code)
    {
        $session = SessionGroup::where('code', $code)
            ->with(['organizer', 'entries.user'])
            ->firstOrFail();

        return Inertia::render('SessionDetail', [
            'session' => $session,
            'userEntry' => SessionGroupEntry::where('session_group_id', $session->id)
                ->where('user_id', auth()->id())
                ->first(),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can_organize) {
            return response()->json(['error' => 'You do not have permission to create poker nights'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'session_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $session = SessionGroup::create([
            'organizer_id' => auth()->id(),
            'name' => $validated['name'],
            'code' => strtoupper(Str::random(6)),
            'session_date' => $validated['session_date'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'session' => $session,
            'code' => $session->code,
        ], 201);
    }

    public function addEntry(Request $request, $code)
    {
        $session = SessionGroup::where('code', $code)->firstOrFail();

        $validated = $request->validate([
            'buy_in' => 'required|numeric|min:0',
            'cash_out' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if user already has an entry
        $existing = SessionGroupEntry::where('session_group_id', $session->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            $existing->update([
                'buy_in' => $validated['buy_in'],
                'cash_out' => $validated['cash_out'],
                'notes' => $validated['notes'] ?? null,
            ]);
            return response()->json($existing);
        }

        $entry = SessionGroupEntry::create([
            'session_group_id' => $session->id,
            'user_id' => auth()->id(),
            'buy_in' => $validated['buy_in'],
            'cash_out' => $validated['cash_out'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($entry, 201);
    }
}

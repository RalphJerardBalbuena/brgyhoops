<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * Get the active tournament for the authenticated user.
     */
    private function getActiveTournament()
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return null;
        }

        return $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();
    }

    /**
     * Display the schedule for the active tournament.
     */
    public function index()
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $games = $activeTournament->games()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();

        return view('games.index', compact('activeTournament', 'games'));
    }

    /**
     * Show the form for creating a scheduled game.
     */
    public function create()
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $teams = $activeTournament->teams()
            ->orderBy('name')
            ->get();

        return view('games.create', compact('activeTournament', 'teams'));
    }

    /**
     * Store a newly scheduled game.
     */
    public function store(Request $request)
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $validated = $request->validate([
            'home_team_id' => 'required|integer|different:away_team_id',
            'away_team_id' => 'required|integer|different:home_team_id',
            'scheduled_at' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        $teamIds = $activeTournament->teams()
            ->whereIn('id', [
                $validated['home_team_id'],
                $validated['away_team_id'],
            ])
            ->pluck('id');

        if ($teamIds->count() !== 2) {
            abort(422, 'Both teams must belong to the active tournament.');
        }

        $activeTournament->games()->create([
            'home_team_id' => $validated['home_team_id'],
            'away_team_id' => $validated['away_team_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('games.index')
            ->with('success', 'Game scheduled successfully.');
    }

    /**
     * Show the form for editing a scheduled game.
     */
    public function edit(Game $game)
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $teams = $activeTournament->teams()
            ->orderBy('name')
            ->get();

        return view('games.edit', compact('activeTournament', 'game', 'teams'));
    }

    /**
     * Update a scheduled game.
     */
    public function update(Request $request, Game $game)
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $validated = $request->validate([
            'home_team_id' => 'required|integer|different:away_team_id',
            'away_team_id' => 'required|integer|different:home_team_id',
            'scheduled_at' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        $teamIds = $activeTournament->teams()
            ->whereIn('id', [
                $validated['home_team_id'],
                $validated['away_team_id'],
            ])
            ->pluck('id');

        if ($teamIds->count() !== 2) {
            abort(422, 'Both teams must belong to the active tournament.');
        }

        $game->update([
            'home_team_id' => $validated['home_team_id'],
            'away_team_id' => $validated['away_team_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('games.index')
            ->with('success', 'Game updated successfully.');
    }

    /**
     * Delete a scheduled game.
     */
    public function destroy(Game $game)
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $game->delete();

        return redirect()
            ->route('games.index')
            ->with('success', 'Game deleted successfully.');
    }
}
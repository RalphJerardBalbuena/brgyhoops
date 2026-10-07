<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Show teams belonging to the active tournament.
     */
    public function index()
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $teams = $activeTournament->teams()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('teams.index', compact('activeTournament', 'teams'));
    }

    /**
     * Show the team creation form.
     */
    public function create()
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        return view('teams.create', compact('activeTournament'));
    }

    /**
     * Store a new team in the active tournament.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $activeTournament->teams()->create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    /**
     * Show the team editing form.
     */
    public function edit(Team $team)
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if (
            $team->tournament_id !== $activeTournament->id ||
            $team->user_id !== $user->id
        ) {
            abort(403);
        }

        return view('teams.edit', compact('activeTournament', 'team'));
    }

    /**
     * Update a team.
     */
    public function update(Request $request, Team $team)
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if (
            $team->tournament_id !== $activeTournament->id ||
            $team->user_id !== $user->id
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $team->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team updated successfully.');
    }

    /**
     * Delete a team.
     */
    public function destroy(Team $team)
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if (
            $team->tournament_id !== $activeTournament->id ||
            $team->user_id !== $user->id
        ) {
            abort(403);
        }

        $team->delete();

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
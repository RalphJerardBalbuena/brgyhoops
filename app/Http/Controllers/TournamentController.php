<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Show tournaments assigned to the logged-in user.
     */
    public function index()
    {
        $tournaments = auth()->user()
            ->tournaments()
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->get();

        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Select a tournament and store it as the active tournament.
     */
    public function select(Request $request, Tournament $tournament)
    {
        $user = auth()->user();

        if (!$user->tournaments()->where('tournaments.id', $tournament->id)->exists()) {
            abort(403);
        }

        session(['active_tournament_id' => $tournament->id]);

        return redirect('/manager');
    }

    /**
     * Show the manager dashboard with the active tournament.
     */
    public function managerDashboard()
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

        return view('manager.dashboard', compact('activeTournament'));
    }

    /**
     * Show the admin dashboard with tournament context.
     */
    public function adminDashboard()
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $tournaments = Tournament::withCount(['teams', 'games'])
            ->orderBy('name')
            ->get();

        return view('admin.dashboard', compact('tournaments'));
    }

    /**
     * Show the default public tournament dashboard.
     *
     * Opens the first ongoing tournament, or the first upcoming
     * tournament if there is no ongoing tournament.
     */
    public function publicDashboard()
    {
        $tournament = Tournament::whereIn('status', ['ongoing', 'upcoming'])
            ->orderByRaw("CASE WHEN status = 'ongoing' THEN 0 ELSE 1 END")
            ->orderBy('start_date')
            ->first();

        if (!$tournament) {
            return view('public.dashboard', [
                'tournament' => null,
                'teams' => collect(),
                'games' => collect(),
            ]);
        }

        return redirect()->route('public.tournament', $tournament);
    }

    /**
     * Show the public dashboard for a specific tournament.
     */
    public function publicTournamentDashboard(Tournament $tournament)
    {
        $teams = $tournament->teams()
            ->orderBy('name')
            ->get();

        $games = $tournament->games()
            ->with(['homeTeam', 'awayTeam'])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get();

        return view('public.dashboard', compact(
            'tournament',
            'teams',
            'games'
        ));
    }
}
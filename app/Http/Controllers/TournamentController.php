<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Show the upcoming and ongoing tournaments the logged-in user may manage.
     */
    public function index()
    {
        $tournaments = auth()->user()
            ->manageableTournaments()
            ->orderBy('tournaments.name')
            ->get();

        $activeTournamentId = (int) session('active_tournament_id');

        return view('tournaments.index', compact('tournaments', 'activeTournamentId'));
    }

    /**
     * Select a tournament and store it as the active tournament.
     */
    public function select(Request $request, Tournament $tournament)
    {
        $user = auth()->user();

        if (!$user->manageableTournaments()->where('tournaments.id', $tournament->id)->exists()) {
            abort(403);
        }

        session(['active_tournament_id' => $tournament->id]);

        if ($user->role === 'admin') {
            return redirect()->route('games.index');
        }

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

        $activeTournament = $user->manageableTournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        // The manager's own teams in this tournament, with their rosters.
        $teams = $activeTournament->teams()
            ->where('user_id', $user->id)
            ->with(['roster' => fn ($query) => $query->orderBy('name')])
            ->withCount([
                'players as pending_requests_count' => fn ($query) => $query->where('player_team.status', 'pending'),
            ])
            ->orderBy('name')
            ->get();

        return view('manager.dashboard', compact('activeTournament', 'teams'));
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
            ->where('application_status', 'approved')
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
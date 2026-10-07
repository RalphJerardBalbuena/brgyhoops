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
}
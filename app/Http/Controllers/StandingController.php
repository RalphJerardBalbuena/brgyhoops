<?php

namespace App\Http\Controllers;

use App\Support\Standings;

class StandingController extends Controller
{
    public function index()
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

        $standings = Standings::forTournament($activeTournament);

        return view('standings.index', compact(
            'activeTournament',
            'standings'
        ));
    }
}

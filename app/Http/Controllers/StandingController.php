<?php

namespace App\Http\Controllers;

use App\Models\Team;
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

    /**
     * Officially eliminate an approved team from the selected, ongoing tournament.
     *
     * Records teams.eliminated_at only. It releases the team's manager from the
     * overlapping-tournament rule but approves nothing; any new tournament
     * still needs an approved application.
     */
    public function eliminate(Team $team)
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'admin', 403);

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

        if ($team->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $redirect = redirect()->route('standings.index');

        if ($activeTournament->status !== 'ongoing') {
            return $redirect->with('error', 'Teams can only be eliminated while the tournament is ongoing.');
        }

        // Compare-and-set: only an approved, not yet eliminated team is updated.
        $updated = Team::whereKey($team->id)
            ->where('application_status', 'approved')
            ->whereNull('eliminated_at')
            ->update(['eliminated_at' => now(), 'updated_at' => now()]);

        if ($updated !== 1) {
            return $redirect->with('error', $team->application_status === 'approved'
                ? "{$team->name} has already been eliminated."
                : 'Only approved teams can be eliminated.');
        }

        return $redirect->with('success', "{$team->name} has been marked as eliminated.");
    }
}

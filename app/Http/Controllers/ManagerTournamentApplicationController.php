<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Support\TournamentApplications;
use Illuminate\Http\RedirectResponse;

/**
 * A team manager applies to take part in an open tournament.
 *
 * Applying creates a pending application only: no tournament_user row and
 * no session change. Access follows only from admin approval.
 */
class ManagerTournamentApplicationController extends Controller
{
    public function store(Tournament $tournament): RedirectResponse
    {
        // Same visibility rule as the details page.
        abort_unless($tournament->isOpenForApplications(), 404);

        $problems = TournamentApplications::submit(auth()->user(), $tournament);

        $redirect = redirect()->route('manager.open-tournaments.show', $tournament);

        if ($problems !== []) {
            return $redirect->with('error', implode(' ', $problems));
        }

        return $redirect->with(
            'success',
            'Application submitted. An administrator will review it; you will get access to the tournament only if it is approved.'
        );
    }
}

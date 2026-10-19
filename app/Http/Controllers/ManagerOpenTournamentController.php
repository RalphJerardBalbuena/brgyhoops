<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\View\View;

/**
 * Read-only listing of open (upcoming) tournaments for team managers.
 *
 * Any team manager may browse these, assigned or not. Browsing grants
 * nothing: it never writes the session or the tournament_user pivot, and
 * workspace access still comes only from an admin assignment
 * (User::manageableTournaments). Applying to participate is a later step.
 */
class ManagerOpenTournamentController extends Controller
{
    /**
     * List open tournaments, soonest first, flagging those already assigned.
     */
    public function index(): View
    {
        $tournaments = Tournament::openForApplications()
            ->orderByRaw('start_date IS NULL')
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        $assignedIds = $this->assignedTournamentIds();

        return view('manager.open-tournaments.index', compact('tournaments', 'assignedIds'));
    }

    /**
     * Show an open tournament's details; tournaments that are not open are not found.
     */
    public function show(Tournament $tournament): View
    {
        abort_unless($tournament->isOpenForApplications(), 404);

        $isAssigned = in_array($tournament->id, $this->assignedTournamentIds(), true);

        return view('manager.open-tournaments.show', compact('tournament', 'isAssigned'));
    }

    /**
     * IDs of tournaments the signed-in manager is assigned to.
     *
     * @return array<int, int>
     */
    private function assignedTournamentIds(): array
    {
        return auth()->user()
            ->tournaments()
            ->pluck('tournaments.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}

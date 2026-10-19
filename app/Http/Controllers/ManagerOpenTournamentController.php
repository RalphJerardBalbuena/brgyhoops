<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\View\View;

/**
 * Read-only listing of open (upcoming) tournaments for team managers.
 *
 * Any team manager may browse these, assigned or not. Browsing grants
 * nothing: it never writes the session or the tournament_user pivot, and
 * workspace access still comes only from participation granted by an
 * approved application (User::manageableTournaments). Applying is handled by
 * ManagerTournamentApplicationController; these pages show its status.
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

        // The manager's own applications, keyed by tournament.
        $applications = auth()->user()
            ->tournamentApplications()
            ->whereIn('tournament_id', $tournaments->modelKeys())
            ->get()
            ->keyBy('tournament_id');

        return view('manager.open-tournaments.index', compact('tournaments', 'assignedIds', 'applications'));
    }

    /**
     * Show an open tournament's details; tournaments that are not open are not found.
     */
    public function show(Tournament $tournament): View
    {
        abort_unless($tournament->isOpenForApplications(), 404);

        $isAssigned = in_array($tournament->id, $this->assignedTournamentIds(), true);

        $application = auth()->user()
            ->tournamentApplications()
            ->where('tournament_id', $tournament->id)
            ->first();

        return view('manager.open-tournaments.show', compact('tournament', 'isAssigned', 'application'));
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

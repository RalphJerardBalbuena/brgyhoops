<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamApplicationSubmitted;
use App\Support\RosterRules;
use App\Support\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        $activeTournament = $user->manageableTournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $teams = $activeTournament->teams()
            ->where('user_id', $user->id)
            ->with(['roster' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('teams.index', compact('activeTournament', 'teams'));
    }

    /**
     * Submit a team's roster for admin approval.
     *
     * Allowed for the team's manager, in the selected tournament, when the team
     * is a draft or was rejected and its roster satisfies the roster rules.
     */
    public function submit(Team $team)
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

        if (
            $team->tournament_id !== $activeTournament->id ||
            $team->user_id !== $user->id
        ) {
            abort(403);
        }

        // Lock the team so the roster cannot change between the check and the submit.
        $problems = DB::transaction(function () use ($team) {
            $locked = Team::whereKey($team->id)->lockForUpdate()->first();

            $problems = RosterRules::submissionProblems($locked);

            if ($problems === []) {
                $locked->forceFill([
                    'application_status' => 'pending',
                    'applied_at' => now(),
                    'reviewed_at' => null,
                    'admin_review_note' => null,
                ])->save();
            }

            return $problems;
        });

        if ($problems !== []) {
            return redirect()
                ->route('teams.index')
                ->with('error', implode(' ', $problems));
        }

        SafeNotifier::send(
            User::where('role', 'admin')->get(),
            new TeamApplicationSubmitted($team->fresh(['tournament', 'manager']))
        );

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team submitted for admin approval.');
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

        $activeTournament = $user->manageableTournaments()
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

        $activeTournament = $user->manageableTournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        // tournament_id must match the selected (and therefore authorized)
        // tournament, so a missing or stale form cannot create a team.
        $validator = Validator::make($request->all(), [
            'tournament_id' => ['required', 'integer', Rule::in([$activeTournament->id])],
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ], [
            'tournament_id.required' => 'The tournament for this form is missing. Please reload the form and try again.',
            'tournament_id.integer' => 'The tournament for this form is invalid. Please reload the form and try again.',
            'tournament_id.in' => 'The selected tournament changed. Please review the form and submit again.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('teams.create')
                ->withErrors($validator)
                ->withInput($request->except('tournament_id'));
        }

        $validated = $validator->validated();

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

        $activeTournament = $user->manageableTournaments()
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

        $activeTournament = $user->manageableTournaments()
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

        $activeTournament = $user->manageableTournaments()
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
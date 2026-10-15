<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Tournament;
use App\Notifications\GameDetails;
use App\Notifications\GameScheduleChanged;
use App\Notifications\GameScheduled;
use App\Support\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    /**
     * Ensure only admins can create, edit, score, or delete games.
     */
    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->role === 'admin',
            403,
            'Access denied. Administrators only.'
        );
    }

    /**
     * Latitude/longitude attributes to save, if any.
     *
     * Empty when the coordinate migration has not run (so saving keeps working
     * on an unmigrated database) or when the request did not send the fields
     * (so an omitted pin is preserved). Submitted blanks clear the pin.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, float|null>
     */
    private function coordinateAttributes(Request $request, array $validated): array
    {
        if (!Game::supportsCoordinates()
            || (!$request->exists('latitude') && !$request->exists('longitude'))) {
            return [];
        }

        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;

        return [
            'latitude' => $latitude === null ? null : round((float) $latitude, 7),
            'longitude' => $longitude === null ? null : round((float) $longitude, 7),
        ];
    }

    /**
     * Managers of the game's home and away teams (freshly loaded).
     *
     * @return array<int, \App\Models\User|null>
     */
    private function teamManagers(Game $game): array
    {
        $game->load(['homeTeam.manager', 'awayTeam.manager', 'tournament']);

        return [$game->homeTeam?->manager, $game->awayTeam?->manager];
    }

    /**
     * Describe schedule-relevant changes made by the last save.
     *
     * Score-only edits and marking a game completed are results, not
     * schedule changes, so they produce no description (and no email).
     *
     * @param  array{matchup: string, scheduled_at: ?string, venue: string, status: string}  $previous
     * @return array<int, string>
     */
    private function describeScheduleChanges(Game $game, array $previous): array
    {
        $changes = [];

        if ($game->wasChanged(['home_team_id', 'away_team_id'])) {
            $changes[] = "Matchup: {$previous['matchup']} → " . GameDetails::matchup($game);
        }

        if ($game->wasChanged('scheduled_at')) {
            $changes[] = "Date & time: {$previous['scheduled_at']} → " . $game->scheduled_at?->format('M d, Y h:i A');
        }

        if ($game->wasChanged('venue')) {
            $changes[] = "Venue: {$previous['venue']} → " . ($game->venue ?: 'To be announced');
        }

        if ($game->wasChanged('status') && $game->status !== 'completed') {
            $changes[] = 'Status: ' . ucfirst($previous['status']) . ' → ' . ucfirst($game->status);
        }

        return $changes;
    }

    /**
     * Get the active tournament for the authenticated user.
     */
    private function getActiveTournament()
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return null;
        }

        return $user->manageableTournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();
    }

    /**
     * Build the game validator for the active tournament.
     *
     * Both teams must belong to the active tournament and be approved. On
     * create, the form's tournament_id must also match it, so a missing or
     * stale form is refused. When editing, a team the game already uses is
     * accepted even if not approved, so existing games stay editable.
     */
    private function gameValidator(Request $request, Tournament $activeTournament, bool $creating, ?Game $game = null)
    {
        $existingTeamIds = $game ? [(int) $game->home_team_id, (int) $game->away_team_id] : [];

        $rules = [
            'home_team_id' => 'required|integer|different:away_team_id',
            'away_team_id' => 'required|integer|different:home_team_id',
            'scheduled_at' => 'required|date',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
            'venue' => 'nullable|string|max:255',
            // Optional exact map pin: both values or neither.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'status' => 'required|in:scheduled,completed,cancelled',
        ];

        if ($creating) {
            $rules['tournament_id'] = ['required', 'integer', Rule::in([$activeTournament->id])];
        }

        $validator = Validator::make($request->all(), $rules, [
            'tournament_id.required' => 'The tournament for this form is missing. Please reload the form and try again.',
            'tournament_id.integer' => 'The tournament for this form is invalid. Please reload the form and try again.',
            'tournament_id.in' => 'The selected tournament changed. Please review the form and submit again.',
            'latitude.required_with' => 'Enter both latitude and longitude for the map pin, or leave both blank.',
            'longitude.required_with' => 'Enter both latitude and longitude for the map pin, or leave both blank.',
            'latitude.between' => 'The latitude must be between -90 and 90.',
            'longitude.between' => 'The longitude must be between -180 and 180.',
        ]);

        $validator->after(function ($validator) use ($request, $activeTournament, $existingTeamIds) {
            // A stale form's teams belong to another tournament; report only the stale form.
            if ($validator->errors()->has('tournament_id')) {
                return;
            }

            foreach (['home_team_id' => 'home', 'away_team_id' => 'away'] as $field => $label) {
                if ($validator->errors()->has($field)) {
                    continue;
                }

                $team = $activeTournament->teams()
                    ->whereKey($request->input($field))
                    ->first();

                if (!$team) {
                    $validator->errors()->add(
                        $field,
                        "The {$label} team must belong to {$activeTournament->name}."
                    );

                    continue;
                }

                $alreadyInGame = in_array((int) $team->id, $existingTeamIds, true);

                if ($team->application_status !== 'approved' && !$alreadyInGame) {
                    $validator->errors()->add(
                        $field,
                        "The {$label} team ({$team->name}) must be approved by an admin before it can be scheduled."
                    );
                }
            }
        });

        return $validator;
    }

    /**
     * Display the schedule for the active tournament.
     */
    public function index()
    {
        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $games = $activeTournament->games()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get();

        return view('games.index', compact('activeTournament', 'games'));
    }

    /**
     * Show the form for creating a scheduled game.
     */
    public function create()
    {
        $this->authorizeAdmin();

        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        // Only admin-approved teams can be scheduled.
        $teams = $activeTournament->teams()
            ->where('application_status', 'approved')
            ->orderBy('name')
            ->get();

        $coordinatesSupported = Game::supportsCoordinates();

        return view('games.create', compact('activeTournament', 'teams', 'coordinatesSupported'));
    }

    /**
     * Store a newly scheduled game.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $validator = $this->gameValidator($request, $activeTournament, true);

        if ($validator->fails()) {
            return redirect()
                ->route('games.create')
                ->withErrors($validator)
                ->withInput($request->except('tournament_id'));
        }

        $validated = $validator->validated();

        $game = $activeTournament->games()->create(array_merge([
            'home_team_id' => $validated['home_team_id'],
            'away_team_id' => $validated['away_team_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'home_score' => $validated['home_score'] ?? null,
            'away_score' => $validated['away_score'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'],
        ], $this->coordinateAttributes($request, $validated)));

        // Only announce upcoming games; entering a past result is not a schedule.
        if ($game->status === 'scheduled') {
            SafeNotifier::send($this->teamManagers($game), new GameScheduled($game));
        }

        return redirect()
            ->route('games.index')
            ->with('success', 'Game scheduled successfully.');
    }

    /**
     * Show the form for editing a scheduled game.
     */
    public function edit(Game $game)
    {
        $this->authorizeAdmin();

        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        // Approved teams, plus the teams this game already uses (so existing
        // games with not-yet-approved teams remain editable).
        $teams = $activeTournament->teams()
            ->where(function ($query) use ($game) {
                $query->where('application_status', 'approved')
                    ->orWhereIn('id', [$game->home_team_id, $game->away_team_id]);
            })
            ->orderBy('name')
            ->get();

        $coordinatesSupported = Game::supportsCoordinates();

        return view('games.edit', compact('activeTournament', 'game', 'teams', 'coordinatesSupported'));
    }

    /**
     * Update a scheduled game.
     */
    public function update(Request $request, Game $game)
    {
        $this->authorizeAdmin();

        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $validator = $this->gameValidator($request, $activeTournament, false, $game);

        if ($validator->fails()) {
            return redirect()
                ->route('games.edit', $game)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Snapshot the schedule before saving so changes can be described.
        $previousManagers = $this->teamManagers($game);
        $previous = [
            'matchup' => GameDetails::matchup($game),
            'scheduled_at' => $game->scheduled_at?->format('M d, Y h:i A'),
            'venue' => $game->venue ?: 'To be announced',
            'status' => $game->status,
        ];

        $game->update(array_merge([
            'home_team_id' => $validated['home_team_id'],
            'away_team_id' => $validated['away_team_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'home_score' => $validated['home_score'] ?? null,
            'away_score' => $validated['away_score'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'],
        ], $this->coordinateAttributes($request, $validated)));

        // Reload team relations first so the new matchup is described correctly.
        $currentManagers = $this->teamManagers($game);
        $changes = $this->describeScheduleChanges($game, $previous);

        if ($changes !== []) {
            SafeNotifier::send(
                array_merge($previousManagers, $currentManagers),
                new GameScheduleChanged($game, $changes)
            );
        }

        return redirect()
            ->route('games.index')
            ->with('success', 'Game updated successfully.');
    }

    /**
     * Delete a scheduled game.
     */
    public function destroy(Game $game)
    {
        $this->authorizeAdmin();

        $activeTournament = $this->getActiveTournament();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        if ($game->tournament_id !== $activeTournament->id) {
            abort(403);
        }

        $game->delete();

        return redirect()
            ->route('games.index')
            ->with('success', 'Game deleted successfully.');
    }
}
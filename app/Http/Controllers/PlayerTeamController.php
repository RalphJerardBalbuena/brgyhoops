<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Notifications\PlayerJoinRequestReceived;
use App\Notifications\PlayerJoinRequestReviewed;
use App\Notifications\PlayerJoinRequestSubmitted;
use App\Support\PlayerTournaments;
use App\Support\RosterRules;
use App\Support\Standings;
use App\Support\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerTeamController extends Controller
{
    /**
     * Player hub for the selected tournament: own team + roster, own requests,
     * teams accepting players, and approved (participating) teams.
     */
    public function index()
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'player', 403);

        $tournaments = PlayerTournaments::selectable($user);
        $activeTournament = PlayerTournaments::current($user, $tournaments);
        $readOnly = $activeTournament ? PlayerTournaments::isReadOnly($activeTournament) : true;

        // Membership comes from the persisted approved player_team row only.
        $myTeam = $activeTournament ? PlayerTournaments::membership($user, $activeTournament) : null;
        $myTeam?->load(['manager', 'roster' => fn ($query) => $query->orderBy('name')]);

        // This player's join requests (any status) for teams in this tournament.
        $myRequests = $activeTournament
            ? $user->joinedTeams()->where('teams.tournament_id', $activeTournament->id)->get()->keyBy('id')
            : collect();

        $withRosterCount = [
            'players as approved_players_count' => fn ($query) => $query->where('player_team.status', 'approved'),
        ];

        // Teams whose roster can still change (draft/rejected) accept requests.
        $teams = $activeTournament && !$readOnly && !$myTeam
            ? $activeTournament->teams()
                ->with('manager')
                ->whereIn('application_status', RosterRules::EDITABLE_STATUSES)
                ->withCount($withRosterCount)
                ->orderBy('name')
                ->get()
                ->each(fn (Team $team) => $team->setRelation('tournament', $activeTournament))
            : collect();

        // Approved teams are public participants; only counts, not other rosters.
        $participatingTeams = $activeTournament
            ? $activeTournament->teams()
                ->where('application_status', 'approved')
                ->withCount($withRosterCount)
                ->orderBy('name')
                ->get()
            : collect();

        return view('player.teams', compact(
            'tournaments',
            'activeTournament',
            'readOnly',
            'myTeam',
            'myRequests',
            'teams',
            'participatingTeams'
        ));
    }

    /**
     * Switch the player's selected tournament.
     */
    public function selectTournament(Request $request)
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'player', 403);

        $validated = $request->validate([
            'tournament_id' => ['required', 'integer'],
        ]);

        $tournament = PlayerTournaments::selectable($user)->firstWhere('id', (int) $validated['tournament_id']);

        abort_unless($tournament, 403);

        session([PlayerTournaments::SESSION_KEY => $tournament->id]);

        $target = $request->input('redirect') === 'schedule' ? 'player.schedule' : 'player.teams.index';

        return redirect()
            ->route($target)
            ->with('success', 'Now viewing ' . $tournament->name . '.');
    }

    /**
     * Read-only games, results, and standings for the selected tournament.
     */
    public function schedule()
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'player', 403);

        $tournaments = PlayerTournaments::selectable($user);
        $activeTournament = PlayerTournaments::current($user, $tournaments);
        $myTeam = $activeTournament ? PlayerTournaments::membership($user, $activeTournament) : null;

        $games = $activeTournament
            ? $activeTournament->games()->with(['homeTeam', 'awayTeam'])->orderBy('scheduled_at')->get()
            : collect();

        $standings = $activeTournament ? Standings::forTournament($activeTournament) : [];

        return view('player.schedule', compact('tournaments', 'activeTournament', 'myTeam', 'games', 'standings'));
    }

    public function requestToJoin(Team $team)
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'player', 403);

        if (!$team->tournament ||
            !in_array($team->tournament->status, ['upcoming', 'ongoing'], true)) {
            return back()->with('error', 'This tournament is not accepting team requests.');
        }

        $existing = $user->joinedTeams()
            ->where('teams.id', $team->id)
            ->first();

        if ($existing && $existing->pivot->status !== 'rejected') {
            return back()->with('error', 'You already have a request or membership with this team.');
        }

        if ($blocker = RosterRules::joinBlocker($team, $user)) {
            return back()->with('error', $blocker);
        }

        DB::transaction(function () use ($user, $team) {
            $user->joinedTeams()->syncWithoutDetaching([
                $team->id => [
                    'status' => 'pending',
                    'updated_at' => now(),
                ],
            ]);

            DB::table('player_team')
                ->where('team_id', $team->id)
                ->where('player_id', $user->id)
                ->update([
                    'status' => 'pending',
                    'updated_at' => now(),
                ]);
        });

        SafeNotifier::send($team->manager, new PlayerJoinRequestSubmitted($team, $user));
        SafeNotifier::send($user, new PlayerJoinRequestReceived($team));

        return back()->with('success', 'Your request has been sent to the team manager.');
    }

    public function requests()
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'team_manager', 403);

        $teams = Team::where('user_id', $user->id)
            ->with([
                'tournament',
                'players' => function ($query) {
                    $query->where('player_team.status', 'pending')
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        return view('manager.player-requests', compact('teams'));
    }

    public function approve(Team $team, $playerId)
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'team_manager', 403);
        abort_unless((int) $team->user_id === (int) $user->id, 403);

        // Lock the team row so concurrent approvals cannot overfill the roster,
        // then re-check every roster rule before assigning the player.
        $result = DB::transaction(function () use ($team, $playerId) {
            $lockedTeam = Team::whereKey($team->id)->lockForUpdate()->first();

            $player = $lockedTeam->players()
                ->where('users.id', $playerId)
                ->where('users.role', 'player')
                ->wherePivot('status', 'pending')
                ->first();

            if (!$player) {
                return ['error' => 'Pending player request not found.'];
            }

            if ($blocker = RosterRules::joinBlocker($lockedTeam, $player)) {
                return ['error' => $blocker];
            }

            $lockedTeam->players()->updateExistingPivot($player->id, [
                'status' => 'approved',
                'updated_at' => now(),
            ]);

            return ['player' => $player];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $player = $result['player'];

        SafeNotifier::send($player, new PlayerJoinRequestReviewed($team, 'approved'));

        return back()->with('success', $player->name . ' has been approved.');
    }

    public function reject(Team $team, $playerId)
    {
        $user = auth()->user();

        abort_unless($user && $user->role === 'team_manager', 403);
        abort_unless((int) $team->user_id === (int) $user->id, 403);

        $player = $team->players()
            ->where('users.id', $playerId)
            ->where('users.role', 'player')
            ->wherePivot('status', 'pending')
            ->first();

        if (!$player) {
            return back()->with('error', 'Pending player request not found.');
        }

        // Rejecting only changes the request; roster membership is untouched.
        $team->players()->updateExistingPivot($player->id, [
            'status' => 'rejected',
            'updated_at' => now(),
        ]);

        SafeNotifier::send($player, new PlayerJoinRequestReviewed($team, 'rejected'));

        return back()->with('success', $player->name . '\'s request has been rejected.');
    }
}
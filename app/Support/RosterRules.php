<?php

namespace App\Support;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Roster rules for the team application workflow.
 *
 * Roster membership is an `approved` row in player_team. Application status
 * lives on teams.application_status: draft → pending → approved | rejected,
 * and a rejected team can be corrected and resubmitted.
 */
class RosterRules
{
    /**
     * Application statuses in which the roster may still change.
     */
    public const EDITABLE_STATUSES = ['draft', 'rejected'];

    public static function minPlayers(): int
    {
        return max(1, (int) config('brgyhoops.roster.min_players', 5));
    }

    public static function maxPlayers(): int
    {
        return max(self::minPlayers(), (int) config('brgyhoops.roster.max_players', 12));
    }

    /**
     * Number of approved players on the team's roster.
     */
    public static function rosterCount(Team $team): int
    {
        return DB::table('player_team')
            ->where('team_id', $team->id)
            ->where('status', 'approved')
            ->count();
    }

    /**
     * Whether the roster can currently be changed (not under review or approved).
     */
    public static function rosterIsEditable(Team $team): bool
    {
        return in_array($team->application_status ?? 'draft', self::EDITABLE_STATUSES, true);
    }

    /**
     * Whether the player already has an approved spot on another team
     * in the same tournament.
     */
    public static function playerOnOtherTeamInTournament(User $player, Team $team): bool
    {
        return DB::table('player_team')
            ->join('teams', 'teams.id', '=', 'player_team.team_id')
            ->where('player_team.player_id', $player->id)
            ->where('player_team.status', 'approved')
            ->where('teams.tournament_id', $team->tournament_id)
            ->where('teams.id', '!=', $team->id)
            ->exists();
    }

    /**
     * Reason a player cannot be added to the team's roster, or null if allowed.
     */
    public static function joinBlocker(Team $team, User $player): ?string
    {
        if (!self::rosterIsEditable($team)) {
            return $team->application_status === 'pending'
                ? 'This team\'s roster is under admin review and cannot change right now.'
                : 'This team\'s roster has been approved and is closed.';
        }

        if (self::rosterCount($team) >= self::maxPlayers()) {
            return 'This team\'s roster is full (' . self::maxPlayers() . ' players).';
        }

        if (self::playerOnOtherTeamInTournament($player, $team)) {
            return 'This player is already on another team in this tournament.';
        }

        return null;
    }

    /**
     * Problems preventing the team from being submitted (empty when eligible).
     *
     * @return array<int, string>
     */
    public static function submissionProblems(Team $team): array
    {
        $problems = [];

        if (!self::rosterIsEditable($team)) {
            $problems[] = $team->application_status === 'pending'
                ? 'This team has already been submitted and is awaiting admin review.'
                : 'This team has already been approved.';

            return $problems;
        }

        $count = self::rosterCount($team);

        if ($count < self::minPlayers()) {
            $problems[] = 'A team needs at least ' . self::minPlayers() . ' approved players (currently ' . $count . ').';
        }

        if ($count > self::maxPlayers()) {
            $problems[] = 'A team can have at most ' . self::maxPlayers() . ' approved players (currently ' . $count . ').';
        }

        return $problems;
    }

    /**
     * Roster-size problems an admin should see before approving (empty when valid).
     *
     * @return array<int, string>
     */
    public static function approvalProblems(Team $team): array
    {
        $count = self::rosterCount($team);

        if ($count < self::minPlayers() || $count > self::maxPlayers()) {
            return ['The roster must have between ' . self::minPlayers() . ' and ' . self::maxPlayers() . ' approved players (currently ' . $count . ').'];
        }

        return [];
    }
}

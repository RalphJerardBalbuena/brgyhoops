<?php

namespace App\Support;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Tournament context for player accounts.
 *
 * Players are not assigned to tournaments the way managers are, so they may
 * view every upcoming or ongoing tournament (the statuses in which join
 * requests are accepted). Completed tournaments are included only when the
 * player was on an approved roster there, and are read-only.
 *
 * The choice is stored in its own session key so it never mixes with the
 * manager/admin `active_tournament_id`.
 */
class PlayerTournaments
{
    public const SESSION_KEY = 'player_tournament_id';

    public const OPEN_STATUSES = ['ongoing', 'upcoming'];

    /**
     * Tournaments the player may select, ongoing first, then upcoming, then completed.
     *
     * @return Collection<int, Tournament>
     */
    public static function selectable(User $player): Collection
    {
        $memberTournamentIds = self::membershipTeams($player)->pluck('tournament_id');

        return Tournament::query()
            ->where(function ($query) use ($memberTournamentIds) {
                $query->whereIn('status', self::OPEN_STATUSES)
                    ->orWhereIn('id', $memberTournamentIds);
            })
            ->orderByRaw("CASE status WHEN 'ongoing' THEN 0 WHEN 'upcoming' THEN 1 ELSE 2 END")
            ->orderByRaw('start_date IS NULL')
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
    }

    /**
     * The selected tournament: the session choice if still selectable, else the
     * player's own team's tournament, else the first selectable tournament.
     */
    public static function current(User $player, ?Collection $selectable = null): ?Tournament
    {
        $selectable ??= self::selectable($player);

        $chosen = $selectable->firstWhere('id', (int) session(self::SESSION_KEY));

        if ($chosen) {
            return $chosen;
        }

        $memberTournamentIds = self::membershipTeams($player)->pluck('tournament_id')->all();

        return $selectable->first(fn (Tournament $t) => in_array($t->id, $memberTournamentIds) && in_array($t->status, self::OPEN_STATUSES, true))
            ?? $selectable->first();
    }

    /**
     * Read-only tournaments (completed): history only, no join actions.
     */
    public static function isReadOnly(Tournament $tournament): bool
    {
        return !in_array($tournament->status, self::OPEN_STATUSES, true);
    }

    /**
     * The player's team in a tournament — an approved player_team row only.
     */
    public static function membership(User $player, Tournament $tournament): ?Team
    {
        return self::membershipTeams($player)->firstWhere('tournament_id', $tournament->id);
    }

    /**
     * Teams the player is an approved member of (any tournament).
     *
     * @return Collection<int, Team>
     */
    private static function membershipTeams(User $player): Collection
    {
        return $player->joinedTeams()
            ->wherePivot('status', 'approved')
            ->get();
    }
}

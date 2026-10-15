<?php

namespace App\Notifications;

use App\Models\Game;

/**
 * Shared, non-sensitive game details for game notifications.
 */
class GameDetails
{
    public static function matchup(Game $game): string
    {
        return ($game->homeTeam?->name ?? 'Home Team') . ' vs ' . ($game->awayTeam?->name ?? 'Away Team');
    }

    /**
     * @return array<string, string|null>
     */
    public static function for(Game $game): array
    {
        return [
            'Tournament' => $game->tournament?->name,
            'Matchup' => self::matchup($game),
            'Date & time' => $game->scheduled_at?->format('l, M d, Y \a\t h:i A'),
            'Venue' => $game->venue ?: 'To be announced',
            'Status' => ucfirst((string) $game->status),
        ];
    }
}

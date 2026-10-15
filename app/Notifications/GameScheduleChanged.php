<?php

namespace App\Notifications;

use App\Models\Game;

/**
 * Sent to the managers of the affected teams when an admin changes a game's
 * teams, date/time, venue, or cancels/reinstates it.
 */
class GameScheduleChanged extends BrgyHoopsNotification
{
    /**
     * @param  array<int, string>  $changes  Human-readable change descriptions.
     */
    public function __construct(public Game $game, public array $changes)
    {
    }

    public function content(object $notifiable): array
    {
        $matchup = GameDetails::matchup($this->game);
        $cancelled = $this->game->status === 'cancelled';

        return [
            'subject' => $cancelled ? "Game cancelled: {$matchup}" : "Schedule update: {$matchup}",
            'heading' => $cancelled ? 'Game cancelled' : 'Game schedule updated',
            'lines' => array_merge(
                [$cancelled
                    ? 'A game involving your team has been cancelled.'
                    : 'A game involving your team has been updated. What changed:'],
                $cancelled ? [] : array_map(fn ($change) => '• ' . $change, $this->changes),
            ),
            'details' => GameDetails::for($this->game),
            'actionText' => 'View tournament schedule',
            'actionUrl' => route('public.tournament', $this->game->tournament_id),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Game;

/**
 * Sent to the managers of both teams when an admin schedules a game.
 */
class GameScheduled extends BrgyHoopsNotification
{
    public function __construct(public Game $game)
    {
    }

    public function content(object $notifiable): array
    {
        $matchup = GameDetails::matchup($this->game);

        return [
            'subject' => "Game scheduled: {$matchup}",
            'heading' => 'New game scheduled',
            'lines' => [
                "A game involving your team has been added to the schedule.",
            ],
            'details' => GameDetails::for($this->game),
            'actionText' => 'View tournament schedule',
            'actionUrl' => route('public.tournament', $this->game->tournament_id),
        ];
    }
}

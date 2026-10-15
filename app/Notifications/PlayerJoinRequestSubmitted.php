<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;

/**
 * Sent to a team's manager when a player requests to join the team.
 */
class PlayerJoinRequestSubmitted extends BrgyHoopsNotification
{
    public function __construct(public Team $team, public User $player)
    {
    }

    public function content(object $notifiable): array
    {
        return [
            'subject' => "New join request for {$this->team->name}",
            'heading' => 'New player join request',
            'lines' => [
                "{$this->player->name} has asked to join {$this->team->name}. Review the request to approve or reject it.",
            ],
            'details' => [
                'Player' => $this->player->name,
                'Team' => $this->team->name,
                'Tournament' => $this->team->tournament?->name,
            ],
            'actionText' => 'Review player requests',
            'actionUrl' => route('manager.players.requests'),
        ];
    }
}

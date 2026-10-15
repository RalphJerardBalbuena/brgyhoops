<?php

namespace App\Notifications;

use App\Models\Team;

/**
 * Receipt sent to a player after they request to join a team.
 */
class PlayerJoinRequestReceived extends BrgyHoopsNotification
{
    public function __construct(public Team $team)
    {
    }

    public function content(object $notifiable): array
    {
        return [
            'subject' => "We received your request to join {$this->team->name}",
            'heading' => 'Join request received',
            'lines' => [
                "Your request to join {$this->team->name} has been sent to the team manager. We'll email you when they review it.",
            ],
            'details' => [
                'Team' => $this->team->name,
                'Tournament' => $this->team->tournament?->name,
                'Status' => 'Pending review',
            ],
            'actionText' => 'View my requests',
            'actionUrl' => route('player.teams.index'),
        ];
    }
}

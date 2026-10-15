<?php

namespace App\Notifications;

use App\Models\Team;

/**
 * Sent to a player when a team manager approves or rejects their join request.
 */
class PlayerJoinRequestReviewed extends BrgyHoopsNotification
{
    public function __construct(public Team $team, public string $status)
    {
    }

    public function content(object $notifiable): array
    {
        $approved = $this->status === 'approved';

        return [
            'subject' => $approved
                ? "You're on {$this->team->name}!"
                : "Update on your request to join {$this->team->name}",
            'heading' => $approved ? 'Join request approved' : 'Join request not approved',
            'lines' => [
                $approved
                    ? "Welcome to {$this->team->name}! The team manager approved your request."
                    : "The manager of {$this->team->name} did not approve your request this time. You can explore other teams or try again later.",
            ],
            'details' => [
                'Team' => $this->team->name,
                'Tournament' => $this->team->tournament?->name,
                'Decision' => $approved ? 'Approved' : 'Rejected',
            ],
            'actionText' => $approved ? 'View my teams' : 'Explore teams',
            'actionUrl' => route('player.teams.index'),
        ];
    }
}

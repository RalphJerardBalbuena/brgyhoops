<?php

namespace App\Notifications;

use App\Models\Team;
use App\Support\RosterRules;

/**
 * Sent to admins when a manager submits a team roster for approval.
 */
class TeamApplicationSubmitted extends BrgyHoopsNotification
{
    public function __construct(public Team $team)
    {
    }

    public function content(object $notifiable): array
    {
        return [
            'subject' => "Team submitted for review: {$this->team->name}",
            'heading' => 'Team awaiting approval',
            'lines' => [
                "{$this->team->name} has been submitted with its roster and is waiting for your review.",
            ],
            'details' => [
                'Team' => $this->team->name,
                'Tournament' => $this->team->tournament?->name,
                'Manager' => $this->team->manager?->name,
                'Roster' => RosterRules::rosterCount($this->team) . ' players',
            ],
            'actionText' => 'Review team applications',
            'actionUrl' => route('admin.team-applications.index'),
        ];
    }
}

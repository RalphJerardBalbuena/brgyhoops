<?php

namespace App\Notifications;

use App\Models\Team;

/**
 * Sent to a team's manager when an admin approves or rejects the team's
 * tournament application.
 */
class TeamApplicationReviewed extends BrgyHoopsNotification
{
    public function __construct(public Team $team)
    {
    }

    public function content(object $notifiable): array
    {
        $approved = $this->team->application_status === 'approved';
        $tournament = $this->team->tournament?->name ?? 'the tournament';

        return [
            'subject' => $approved
                ? "{$this->team->name} was approved"
                : "{$this->team->name} needs changes",
            'heading' => $approved ? 'Team application approved' : 'Team application not approved',
            'lines' => [
                $approved
                    ? "Good news! Your team {$this->team->name} has been approved to play in {$tournament}."
                    : "Your team {$this->team->name} was not approved for {$tournament}. Please review the note below, update your team, and resubmit.",
            ],
            'details' => [
                'Team' => $this->team->name,
                'Tournament' => $tournament,
                'Decision' => $approved ? 'Approved' : 'Rejected',
                'Review note' => $this->team->admin_review_note,
            ],
            'actionText' => 'Open BrgyHoops',
            'actionUrl' => route('tournaments.index'),
        ];
    }
}

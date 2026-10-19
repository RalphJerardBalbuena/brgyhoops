<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A team manager's application to take part in a tournament.
 *
 * Not to be confused with a team's roster application (teams.application_status).
 * Status moves pending → approved | rejected | conflict; only approval grants
 * workspace access, by adding the manager to tournament_user (see
 * App\Support\TournamentApplications). "conflict" is set automatically when
 * another, overlapping application of the same manager is approved.
 */
class TournamentApplication extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'conflict'];

    /**
     * Status and review fields are written only by the review workflow.
     */
    protected $fillable = [
        'tournament_id',
        'user_id',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * Badge CSS modifier for the status (conflict reuses the cancelled style).
     */
    public function badgeClass(): string
    {
        return $this->status === 'conflict' ? 'cancelled' : $this->status;
    }

    /**
     * Short status label for badges.
     */
    public function statusLabel(): string
    {
        return $this->status === 'conflict' ? 'Schedule conflict' : ucfirst($this->status);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

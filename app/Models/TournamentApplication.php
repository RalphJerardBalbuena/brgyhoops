<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A team manager's application to take part in a tournament.
 *
 * Not to be confused with a team's roster application (teams.application_status).
 * Status moves pending → approved | rejected; only approval grants workspace
 * access, by adding the manager to tournament_user (see App\Support\TournamentApplications).
 */
class TournamentApplication extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

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

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

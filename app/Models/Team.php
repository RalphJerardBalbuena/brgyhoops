<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'name',
        'short_name',
        'description',
    ];

    /**
     * eliminated_at is set only by the admin elimination action (not fillable).
     */
    protected $casts = [
        'eliminated_at' => 'datetime',
    ];

    /**
     * Whether an admin officially eliminated the team from its tournament.
     */
    public function isEliminated(): bool
    {
        return $this->eliminated_at !== null;
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'player_team',
            'team_id',
            'player_id'
        )->withPivot('status')->withTimestamps();
    }

    /**
     * Players on the team's roster (approved join requests).
     */
    public function roster(): BelongsToMany
    {
        return $this->players()->wherePivot('status', 'approved');
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }
}
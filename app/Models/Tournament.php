<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Tournament extends Model
{
    /**
     * Statuses that may be selected for active management.
     */
    public const MANAGEABLE_STATUSES = ['upcoming', 'ongoing'];

    protected $fillable = [
        'name',
        'type',
        'venue',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Whether the tournaments.venue column exists (its migration has run).
     */
    public static function supportsVenue(): bool
    {
        return Schema::hasColumn('tournaments', 'venue');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
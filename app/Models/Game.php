<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\GoogleMaps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Game extends Model
{
    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'home_score',
        'away_score',
        'venue',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Whether the games.latitude/longitude columns exist (their migration has run).
     */
    public static function supportsCoordinates(): bool
    {
        return Schema::hasColumn('games', 'latitude')
            && Schema::hasColumn('games', 'longitude');
    }

    /**
     * Whether the game has a valid exact map pin.
     */
    public function hasCoordinates(): bool
    {
        return GoogleMaps::validCoordinates($this->latitude, $this->longitude);
    }

    /**
     * Google Maps directions link to the exact pin, or null without one.
     */
    public function coordinateDirectionsUrl(): ?string
    {
        return GoogleMaps::coordinateDirectionsUrl($this->latitude, $this->longitude);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class);
    }

    /**
     * Tournaments this user may select for active management.
     *
     * Admins may manage any upcoming or ongoing tournament; team managers
     * only those they are assigned to. Completed tournaments (by date) are excluded.
     */
    public function manageableTournaments()
    {
        $query = $this->role === 'admin'
            ? Tournament::query()
            : $this->tournaments();

        return $query->manageable();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function joinedTeams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'player_team',
            'player_id',
            'team_id'
        )->withPivot('status')->withTimestamps();
    }
}
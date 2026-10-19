<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class Tournament extends Model
{
    /**
     * Every tournament lifecycle status.
     */
    public const STATUSES = ['upcoming', 'ongoing', 'completed'];

    /**
     * Statuses that may be selected for active management.
     */
    public const MANAGEABLE_STATUSES = ['upcoming', 'ongoing'];

    /**
     * Display labels for tournament types.
     */
    public const TYPE_LABELS = [
        'inter_zone' => 'Inter-Zone',
        'inter_barangay' => 'Inter-Barangay',
        'inter_town' => 'Inter-Town',
    ];

    /**
     * Status is derived from the dates; it stays fillable only so legacy
     * records (missing a date) and internal code can still set it.
     */
    protected $fillable = [
        'name',
        'type',
        'venue',
        'prize_pool',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'prize_pool' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Keep the stored column in step with the dates whenever a dated
        // tournament is saved. Undated (legacy) records keep their status.
        static::saving(function (Tournament $tournament) {
            $status = static::statusForDates(
                $tournament->getAttributes()['start_date'] ?? null,
                $tournament->getAttributes()['end_date'] ?? null,
            );

            if ($status !== null) {
                $tournament->setAttribute('status', $status);
            }
        });
    }

    /**
     * Whether the tournaments.venue column exists (its migration has run).
     */
    public static function supportsVenue(): bool
    {
        return Schema::hasColumn('tournaments', 'venue');
    }

    /**
     * Whether the tournaments.prize_pool column exists (its migration has run).
     */
    public static function supportsPrizePool(): bool
    {
        return Schema::hasColumn('tournaments', 'prize_pool');
    }

    /**
     * The prize pool as pesos (e.g. "₱50,000.00"), or null when not set.
     */
    public function formattedPrizePool(): ?string
    {
        $amount = $this->getAttributes()['prize_pool'] ?? null;

        return $amount === null ? null : '₱' . number_format((float) $amount, 2);
    }

    /**
     * Display label for the tournament type.
     */
    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Basketball Tournament';
    }

    /**
     * Today's date in the tournament status timezone (Asia/Manila by default).
     */
    public static function today(): CarbonInterface
    {
        return Carbon::now(config('app.tournament_timezone', 'Asia/Manila'))->startOfDay();
    }

    /**
     * The lifecycle status for a date range, or null when either date is missing.
     *
     * Both dates are inclusive calendar days: upcoming before the start date,
     * ongoing from the start date through the end date, completed afterwards.
     */
    public static function statusForDates(mixed $startDate, mixed $endDate, ?CarbonInterface $today = null): ?string
    {
        if (blank($startDate) || blank($endDate)) {
            return null;
        }

        $today = ($today ?? static::today())->toDateString();
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        if ($today < $start) {
            return 'upcoming';
        }

        return $today <= $end ? 'ongoing' : 'completed';
    }

    /**
     * The current status: derived from the dates when both are set, otherwise
     * the stored value (legacy records keep their status until edited).
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => static::statusForDates(
                $attributes['start_date'] ?? null,
                $attributes['end_date'] ?? null,
            ) ?? $value,
        );
    }

    /**
     * Whether the tournament is upcoming or ongoing (open for management and joining).
     */
    public function isManageable(): bool
    {
        return in_array($this->status, self::MANAGEABLE_STATUSES, true);
    }

    /**
     * Limit to tournaments whose current status is one of the given statuses.
     *
     * Mirrors the status accessor in SQL: dated tournaments are matched by
     * comparing their dates with today, undated ones by the stored status.
     *
     * @param  string|array<int, string>  $statuses
     */
    public function scopeWithStatus(Builder $query, string|array $statuses): void
    {
        $statuses = array_values(array_intersect(self::STATUSES, (array) $statuses));
        $today = static::today()->toDateString();
        $start = $this->qualifyColumn('start_date');
        $end = $this->qualifyColumn('end_date');

        $query->where(function ($query) use ($statuses, $today, $start, $end) {
            $query->where(function ($dated) use ($statuses, $today, $start, $end) {
                $dated->whereNotNull($start)
                    ->whereNotNull($end)
                    ->where(function ($matches) use ($statuses, $today, $start, $end) {
                        foreach ($statuses as $status) {
                            $matches->orWhere(fn ($range) => match ($status) {
                                'upcoming' => $range->whereDate($start, '>', $today),
                                'ongoing' => $range->whereDate($start, '<=', $today)->whereDate($end, '>=', $today),
                                'completed' => $range->whereDate($end, '<', $today),
                            });
                        }

                        if ($statuses === []) {
                            $matches->whereRaw('1 = 0');
                        }
                    });
            })->orWhere(function ($legacy) use ($statuses, $start, $end) {
                $legacy->where(fn ($missing) => $missing->whereNull($start)->orWhereNull($end))
                    ->whereIn($this->qualifyColumn('status'), $statuses);
            });
        });
    }

    /**
     * Limit to upcoming and ongoing tournaments.
     */
    public function scopeManageable(Builder $query): void
    {
        $query->withStatus(self::MANAGEABLE_STATUSES);
    }

    /**
     * Whether managers may browse the tournament as open (it has not started yet).
     */
    public function isOpenForApplications(): bool
    {
        return $this->status === 'upcoming';
    }

    /**
     * Limit to tournaments open to managers: upcoming only (Asia/Manila date).
     *
     * Browsing an open tournament grants no access; workspace access still
     * comes only from an admin assignment (see User::manageableTournaments).
     */
    public function scopeOpenForApplications(Builder $query): void
    {
        $query->withStatus('upcoming');
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

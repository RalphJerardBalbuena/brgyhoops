<?php

namespace Tests\Unit;

use App\Models\Tournament;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TournamentStatusTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function boundaries(): array
    {
        return [
            'day before start' => ['2026-11-10', '2026-11-12', '2026-11-09', 'upcoming'],
            'start day' => ['2026-11-10', '2026-11-12', '2026-11-10', 'ongoing'],
            'middle day' => ['2026-11-10', '2026-11-12', '2026-11-11', 'ongoing'],
            'end day' => ['2026-11-10', '2026-11-12', '2026-11-12', 'ongoing'],
            'day after end' => ['2026-11-10', '2026-11-12', '2026-11-13', 'completed'],
            'single-day event, before' => ['2026-11-10', '2026-11-10', '2026-11-09', 'upcoming'],
            'single-day event, on the day' => ['2026-11-10', '2026-11-10', '2026-11-10', 'ongoing'],
            'single-day event, after' => ['2026-11-10', '2026-11-10', '2026-11-11', 'completed'],
            'year boundary' => ['2026-12-31', '2027-01-01', '2027-01-01', 'ongoing'],
        ];
    }

    #[DataProvider('boundaries')]
    public function test_status_is_derived_from_inclusive_dates(string $start, string $end, string $today, string $expected): void
    {
        $this->assertSame($expected, Tournament::statusForDates($start, $end, Carbon::parse($today)));
    }

    public function test_time_of_day_on_stored_dates_is_ignored(): void
    {
        $today = Carbon::parse('2026-11-12 23:59:59');

        $this->assertSame('ongoing', Tournament::statusForDates('2026-11-10 00:00:00', '2026-11-12 00:00:00', $today));
        $this->assertSame('ongoing', Tournament::statusForDates(Carbon::parse('2026-11-10'), Carbon::parse('2026-11-12'), $today));
    }

    public function test_missing_dates_yield_no_derived_status(): void
    {
        $today = Carbon::parse('2026-11-11');

        $this->assertNull(Tournament::statusForDates(null, null, $today));
        $this->assertNull(Tournament::statusForDates('2026-11-10', null, $today));
        $this->assertNull(Tournament::statusForDates(null, '2026-11-12', $today));
        $this->assertNull(Tournament::statusForDates('', '', $today));
    }
}

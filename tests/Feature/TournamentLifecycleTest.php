<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\PlayerTournaments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sprint 3 / Ticket 1: tournament status is derived from the start and end
 * dates (inclusive, in Asia/Manila) instead of being chosen by an admin.
 */
class TournamentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function dated(string $name, string $start, string $end, array $attributes = []): Tournament
    {
        return Tournament::create(['name' => $name, 'start_date' => $start, 'end_date' => $end] + $attributes);
    }

    /*
    |--------------------------------------------------------------------------
    | Derived status
    |--------------------------------------------------------------------------
    */

    public function test_status_follows_the_dates_as_time_passes_without_any_save(): void
    {
        $tournament = $this->dated('Clockwork Cup', '2026-11-10', '2026-11-12');

        $expectations = [
            '2026-11-09 04:00:00' => 'upcoming',
            '2026-11-10 04:00:00' => 'ongoing',
            '2026-11-12 04:00:00' => 'ongoing',
            '2026-11-13 04:00:00' => 'completed',
        ];

        foreach ($expectations as $now => $expected) {
            $this->travelTo($now);

            $this->assertSame($expected, $tournament->fresh()->status, "at {$now}");
            $this->assertSame([$tournament->id], Tournament::withStatus($expected)->pluck('id')->all(), "query at {$now}");
        }
    }

    public function test_today_is_decided_in_asia_manila_while_the_app_stays_utc(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('Asia/Manila', config('app.tournament_timezone'));

        $tournament = $this->dated('Midnight Cup', '2026-11-10', '2026-11-12');

        // Manila is UTC+8: local midnight is 16:00 UTC the previous day.
        $expectations = [
            '2026-11-09 15:59:59' => 'upcoming',   // 23:59:59 on Nov 9 in Manila
            '2026-11-09 16:00:00' => 'ongoing',    // 00:00 on Nov 10 in Manila
            '2026-11-12 15:59:59' => 'ongoing',    // 23:59:59 on Nov 12 (end day)
            '2026-11-12 16:00:00' => 'completed',  // 00:00 on Nov 13
        ];

        foreach ($expectations as $now => $expected) {
            $this->travelTo($now);

            $this->assertSame($expected, $tournament->fresh()->status, "at {$now} UTC");
            $this->assertTrue(Tournament::withStatus($expected)->whereKey($tournament->id)->exists(), "query at {$now} UTC");
        }
    }

    public function test_saving_stores_the_derived_status_and_overrides_a_supplied_one(): void
    {
        $tournament = $this->dated('Forged Cup', '2026-12-01', '2026-12-20', ['status' => 'completed']);

        $this->assertSame('upcoming', $tournament->getRawOriginal('status'));
        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'status' => 'upcoming']);

        $tournament->update(['start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'status' => 'completed']);
    }

    public function test_legacy_tournaments_missing_a_date_keep_their_stored_status(): void
    {
        $undated = Tournament::create(['name' => 'Legacy Final', 'status' => 'completed']);
        $startOnly = Tournament::create(['name' => 'Legacy Start', 'status' => 'completed', 'start_date' => '2026-01-01']);
        $endOnly = Tournament::create(['name' => 'Legacy End', 'status' => 'ongoing', 'end_date' => '2026-01-31']);

        $this->travelTo('2027-06-01 04:00:00');

        $this->assertSame('completed', $undated->fresh()->status);
        $this->assertSame('completed', $startOnly->fresh()->status);
        $this->assertSame('ongoing', $endOnly->fresh()->status);

        $this->assertEqualsCanonicalizing(
            [$undated->id, $startOnly->id],
            Tournament::withStatus('completed')->pluck('id')->all()
        );
        $this->assertSame([$endOnly->id], Tournament::manageable()->pluck('id')->all());
    }

    public function test_query_filters_agree_with_the_status_attribute(): void
    {
        $this->dated('Past Cup', '2026-10-01', '2026-10-31');
        $this->dated('Ends Today Cup', '2026-10-25', '2026-11-01');
        $this->dated('Starts Today Cup', '2026-11-01', '2026-11-05');
        $this->dated('Future Cup', '2026-11-02', '2026-11-30');
        Tournament::create(['name' => 'Legacy Upcoming', 'status' => 'upcoming']);
        Tournament::create(['name' => 'Legacy Ongoing', 'status' => 'ongoing']);
        Tournament::create(['name' => 'Legacy Completed', 'status' => 'completed']);

        $all = Tournament::all();

        foreach (Tournament::STATUSES as $status) {
            $this->assertEqualsCanonicalizing(
                $all->filter(fn (Tournament $t) => $t->status === $status)->pluck('id')->all(),
                Tournament::withStatus($status)->pluck('id')->all(),
                "status {$status}"
            );
        }

        $this->assertEqualsCanonicalizing(
            $all->filter->isManageable()->pluck('id')->all(),
            Tournament::manageable()->pluck('id')->all()
        );
        $this->assertSame(
            ['Ends Today Cup', 'Starts Today Cup', 'Future Cup', 'Legacy Upcoming', 'Legacy Ongoing'],
            Tournament::manageable()->orderBy('id')->pluck('name')->all()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admin create & edit
    |--------------------------------------------------------------------------
    */

    public function test_admin_forms_have_no_manual_status_selection(): void
    {
        $tournament = $this->dated('Form Cup', '2026-10-25', '2026-11-05');

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertDontSee('Set automatically from the dates')
            ->assertSee('id="start_date" name="start_date" type="date" value="" required', false)
            ->assertSee('id="end_date" name="end_date" type="date" value="" required', false);

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $tournament))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertDontSee('Set automatically from the dates')
            ->assertDontSee('<span class="badge badge-ongoing">', false);

        // Status is still derived from the dates.
        $this->assertSame('ongoing', $tournament->fresh()->status);
    }

    public function test_creating_derives_status_and_ignores_a_submitted_status(): void
    {
        $cases = [
            'Future Cup' => ['2026-11-02', '2026-11-30', 'upcoming'],
            'Running Cup' => ['2026-11-01', '2026-11-01', 'ongoing'],
            'Finished Cup' => ['2026-10-01', '2026-10-31', 'completed'],
        ];

        foreach ($cases as $name => [$start, $end, $expected]) {
            $this->actingAs($this->admin)
                ->post(route('admin.tournaments.store'), [
                    'name' => $name,
                    'type' => 'inter_barangay',
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => $expected === 'completed' ? 'upcoming' : 'completed',
                ])
                ->assertRedirect(route('admin.tournaments.index'))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('tournaments', ['name' => $name, 'status' => $expected]);
        }
    }

    public function test_start_and_end_dates_are_required_on_create(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tournaments.create'))
            ->post(route('admin.tournaments.store'), [
                'name' => 'Dateless Cup',
                'type' => 'inter_barangay',
                'status' => 'ongoing',
            ])
            ->assertRedirect(route('admin.tournaments.create'))
            ->assertSessionHasErrors(['start_date', 'end_date'])
            ->assertSessionDoesntHaveErrors('status');

        $this->assertDatabaseMissing('tournaments', ['name' => 'Dateless Cup']);
    }

    public function test_editing_dates_recalculates_status_and_ignores_a_submitted_status(): void
    {
        $tournament = $this->dated('Shifting Cup', '2026-10-25', '2026-11-05');

        $this->actingAs($this->admin)
            ->put(route('admin.tournaments.update', $tournament), [
                'name' => 'Shifting Cup',
                'type' => 'inter_barangay',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
                'status' => 'ongoing',
            ])
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'status' => 'completed']);

        // A completed tournament links to its results instead of the workspace.
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.index'))
            ->assertSee('href="' . route('public.tournament', $tournament) . '"', false)
            ->assertDontSee('action="' . route('tournaments.select', $tournament) . '"', false);

        // Moving the dates forward reopens it.
        $this->actingAs($this->admin)->put(route('admin.tournaments.update', $tournament), [
            'name' => 'Shifting Cup',
            'type' => 'inter_barangay',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-10',
        ]);

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'status' => 'upcoming']);
    }

    public function test_editing_a_legacy_undated_tournament_requires_dates_and_keeps_status_until_then(): void
    {
        $legacy = Tournament::create(['name' => 'Legacy League', 'type' => 'inter_zone', 'status' => 'ongoing']);

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $legacy))
            ->assertOk()
            ->assertSee('value="Legacy League"', false);

        $this->actingAs($this->admin)
            ->from(route('admin.tournaments.edit', $legacy))
            ->put(route('admin.tournaments.update', $legacy), [
                'name' => 'Legacy League Renamed',
                'type' => 'inter_zone',
            ])
            ->assertRedirect(route('admin.tournaments.edit', $legacy))
            ->assertSessionHasErrors(['start_date', 'end_date']);

        $this->assertDatabaseHas('tournaments', ['id' => $legacy->id, 'name' => 'Legacy League', 'status' => 'ongoing']);

        $this->actingAs($this->admin)
            ->put(route('admin.tournaments.update', $legacy), [
                'name' => 'Legacy League',
                'type' => 'inter_zone',
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-31',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournaments', ['id' => $legacy->id, 'status' => 'upcoming']);
    }

    /*
    |--------------------------------------------------------------------------
    | Access follows the derived status
    |--------------------------------------------------------------------------
    */

    public function test_manager_access_lasts_through_the_end_day_and_stops_the_day_after(): void
    {
        $manager = User::factory()->create(['role' => 'team_manager']);
        $tournament = $this->dated('Final Week Cup', '2026-10-25', '2026-11-03');
        $tournament->users()->attach($manager->id);

        // 23:59 on the end day in Manila.
        $this->travelTo('2026-11-03 15:59:00');

        $this->actingAs($manager)
            ->post(route('tournaments.select', $tournament))
            ->assertRedirect('/manager');

        $this->actingAs($manager)->get('/manager')->assertOk()->assertSee('Final Week Cup');

        // 00:00 the next day in Manila: completed, without anyone saving it.
        $this->travelTo('2026-11-03 16:00:00');

        $this->actingAs($manager)
            ->get('/manager')
            ->assertRedirect(route('tournaments.index'));

        $this->actingAs($manager)
            ->post(route('tournaments.select', $tournament))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertDontSee('Final Week Cup');

        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'status' => 'ongoing']);
    }

    public function test_public_dashboard_prefers_ongoing_then_earliest_upcoming_by_date(): void
    {
        $legacyUpcoming = Tournament::create(['name' => 'Undated Upcoming', 'status' => 'upcoming']);
        $later = $this->dated('Later Cup', '2026-11-20', '2026-11-30');
        $sooner = $this->dated('Sooner Cup', '2026-11-05', '2026-11-08');

        // Nothing is ongoing yet: the undated upcoming one sorts first (no start date).
        $this->get(route('public.dashboard'))->assertRedirect(route('public.tournament', $legacyUpcoming));

        $legacyUpcoming->delete();
        $this->get(route('public.dashboard'))->assertRedirect(route('public.tournament', $sooner));

        $this->travelTo('2026-11-21 04:00:00');
        $this->get(route('public.dashboard'))->assertRedirect(route('public.tournament', $later));

        $this->travelTo('2026-12-01 04:00:00');
        $this->get(route('public.dashboard'))->assertOk();
    }

    public function test_player_tournaments_and_join_requests_follow_the_dates(): void
    {
        $player = User::factory()->create(['role' => 'player']);
        $manager = User::factory()->create(['role' => 'team_manager']);

        $ongoing = $this->dated('Ongoing Cup', '2026-10-25', '2026-11-03');
        $upcoming = $this->dated('Upcoming Cup', '2026-11-10', '2026-11-20');
        $this->dated('Finished Cup', '2026-10-01', '2026-10-10');

        $this->assertSame(
            ['Ongoing Cup', 'Upcoming Cup'],
            PlayerTournaments::selectable($player)->pluck('name')->all()
        );

        $team = Team::create(['tournament_id' => $ongoing->id, 'user_id' => $manager->id, 'name' => 'Late Joiners']);

        // After the end day (Manila), join requests are refused.
        $this->travelTo('2026-11-03 16:00:00');

        $this->assertSame(['Upcoming Cup'], PlayerTournaments::selectable($player)->pluck('name')->all());
        $this->assertTrue(PlayerTournaments::isReadOnly($ongoing->fresh()));

        $this->actingAs($player)
            ->from(route('player.teams.index'))
            ->post(route('player.teams.request', $team))
            ->assertRedirect(route('player.teams.index'))
            ->assertSessionHas('error', 'This tournament is not accepting team requests.');

        $this->assertDatabaseMissing('player_team', ['team_id' => $team->id, 'player_id' => $player->id]);

        // A team in a tournament that has started by date accepts requests.
        $this->travelTo('2026-11-10 04:00:00');
        $openTeam = Team::create(['tournament_id' => $upcoming->id, 'user_id' => $manager->id, 'name' => 'On Time']);

        $this->actingAs($player)
            ->post(route('player.teams.request', $openTeam))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('player_team', ['team_id' => $openTeam->id, 'player_id' => $player->id, 'status' => 'pending']);
    }
}

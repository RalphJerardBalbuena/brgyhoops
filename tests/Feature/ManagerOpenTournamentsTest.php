<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * S3.9: team managers browse open (upcoming) tournaments without an
 * assignment. Browsing is read-only and grants no workspace access.
 */
class ManagerOpenTournamentsTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->manager = User::factory()->create(['role' => 'team_manager']);
    }

    private function tournament(string $name, string $start, string $end, array $attributes = []): Tournament
    {
        return Tournament::create(array_merge([
            'name' => $name,
            'type' => 'inter_barangay',
            'start_date' => $start,
            'end_date' => $end,
        ], $attributes));
    }

    private function open(string $name = 'Open Cup', array $attributes = []): Tournament
    {
        return $this->tournament($name, '2026-11-10', '2026-11-20', $attributes);
    }

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function test_unassigned_manager_sees_only_upcoming_tournaments(): void
    {
        $this->open('Upcoming Cup');
        $this->tournament('Ongoing Cup', '2026-10-25', '2026-11-05');
        $this->tournament('Completed Cup', '2026-10-01', '2026-10-10');
        Tournament::create(['name' => 'Legacy Upcoming', 'status' => 'upcoming']);
        Tournament::create(['name' => 'Legacy Ongoing', 'status' => 'ongoing']);
        Tournament::create(['name' => 'Legacy Completed', 'status' => 'completed']);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSee('Upcoming Cup')
            ->assertSee('Legacy Upcoming')
            ->assertDontSee('Ongoing Cup')
            ->assertDontSee('Completed Cup')
            ->assertDontSee('Legacy Ongoing')
            ->assertDontSee('Legacy Completed')
            ->assertSee('<strong>2</strong>', false);
    }

    public function test_listing_is_ordered_by_start_date_then_name(): void
    {
        $this->tournament('Zulu Late', '2026-12-01', '2026-12-05');
        $this->tournament('Bravo Soon', '2026-11-05', '2026-11-08');
        $this->tournament('Alpha Soon', '2026-11-05', '2026-11-09');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertSeeInOrder(['Alpha Soon', 'Bravo Soon', 'Zulu Late']);
    }

    public function test_listing_shows_name_type_dates_venue_and_prize_pool(): void
    {
        $tournament = $this->open('Full Detail Cup', [
            'type' => 'inter_town',
            'venue' => 'San Isidro Covered Court, Quezon City',
            'prize_pool' => '50000',
        ]);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSee('Full Detail Cup')
            ->assertSee('Inter-Town')
            ->assertSee('Nov 10, 2026')
            ->assertSee('Nov 20, 2026')
            ->assertSee('San Isidro Covered Court, Quezon City')
            ->assertSee('Prize Pool')
            ->assertSee('₱50,000.00')
            ->assertDontSee('Not announced')
            ->assertSee('href="' . route('manager.open-tournaments.show', $tournament) . '"', false);
    }

    public function test_listing_hides_empty_venue_and_shows_prize_pool_fallback(): void
    {
        $this->open('Bare Cup', ['venue' => null, 'prize_pool' => null]);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSee('Bare Cup')
            ->assertDontSee('Venue:')
            ->assertSeeInOrder(['Prize Pool', 'Not announced'])
            ->assertDontSee('₱');
    }

    public function test_empty_listing_shows_an_empty_state(): void
    {
        $this->tournament('Ongoing Cup', '2026-10-25', '2026-11-05');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSee('No Open Tournaments')
            ->assertDontSee('Ongoing Cup');
    }

    /*
    |--------------------------------------------------------------------------
    | Details
    |--------------------------------------------------------------------------
    */

    public function test_details_show_tournament_information_and_request_button(): void
    {
        $tournament = $this->open('Detail Cup', [
            'type' => 'inter_zone',
            'venue' => 'Marikina Sports Center',
            'prize_pool' => '125000.50',
            'description' => 'Rules and registration notes.',
        ]);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Detail Cup')
            ->assertSee('Inter-Zone')
            ->assertSee('Nov 10, 2026')
            ->assertSee('Nov 20, 2026')
            ->assertSee('Marikina Sports Center')
            ->assertSee('₱125,000.50')
            ->assertDontSee('Not announced')
            ->assertSee('Rules and registration notes.')
            ->assertSee('Request to participate')
            ->assertSee('action="' . route('manager.open-tournaments.apply', $tournament) . '"', false)
            ->assertSee('Participation requires admin approval.')
            ->assertSee('href="' . route('public.tournament', $tournament) . '"', false)
            ->assertDontSee('action="' . route('tournaments.select', $tournament) . '"', false)
            ->assertDontSee('<span class="badge badge-dark">Assigned</span>', false);
    }

    public function test_details_hide_empty_venue_and_show_prize_pool_fallback(): void
    {
        $tournament = $this->open('Bare Detail Cup');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $tournament))
            ->assertOk()
            ->assertSeeInOrder(['Prize Pool', 'Not announced'])
            ->assertDontSee('₱')
            ->assertDontSee('>Venue<', false)
            ->assertDontSee('venue-map', false);
    }

    public function test_details_are_not_found_for_tournaments_that_are_not_open(): void
    {
        $ongoing = $this->tournament('Ongoing Cup', '2026-10-25', '2026-11-05');
        $completed = $this->tournament('Completed Cup', '2026-10-01', '2026-10-10');
        $legacyOngoing = Tournament::create(['name' => 'Legacy Ongoing', 'status' => 'ongoing']);

        foreach ([$ongoing, $completed, $legacyOngoing] as $tournament) {
            $this->actingAs($this->manager)
                ->get(route('manager.open-tournaments.show', $tournament))
                ->assertNotFound();
        }

        $this->actingAs($this->manager)->get('/manager/open-tournaments/999999')->assertNotFound();

        // Not found even when assigned: the workspace is reached through /tournaments.
        $ongoing->users()->attach($this->manager->id);
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $ongoing))
            ->assertNotFound();
    }

    public function test_tournament_stops_being_open_at_manila_midnight_on_its_start_date(): void
    {
        $tournament = $this->open('Boundary Cup');

        // 23:59:59 on Nov 9 in Manila: still upcoming.
        $this->travelTo('2026-11-09 15:59:59');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertSee('Boundary Cup');
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $tournament))
            ->assertOk();

        // 00:00 on Nov 10 in Manila: ongoing, no longer open.
        $this->travelTo('2026-11-09 16:00:00');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertDontSee('Boundary Cup');
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $tournament))
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned tournaments
    |--------------------------------------------------------------------------
    */

    public function test_assigned_tournaments_show_badge_and_workspace_action(): void
    {
        $assigned = $this->open('Assigned Cup');
        $other = $this->tournament('Other Cup', '2026-11-12', '2026-11-22');
        $assigned->users()->attach($this->manager->id);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSeeInOrder(['<span class="badge badge-dark">Assigned</span>', 'Assigned Cup', 'Open workspace', 'Other Cup'], false)
            ->assertSee('action="' . route('tournaments.select', $assigned) . '"', false)
            ->assertDontSee('action="' . route('tournaments.select', $other) . '"', false);
        $this->assertSame(1, substr_count(
            $this->actingAs($this->manager)->get(route('manager.open-tournaments.index'))->getContent(),
            '<span class="badge badge-dark">Assigned</span>'
        ));

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $assigned))
            ->assertOk()
            ->assertSee('<span class="badge badge-dark">Assigned</span>', false)
            ->assertSee('You are assigned to this tournament')
            ->assertSee('action="' . route('tournaments.select', $assigned) . '"', false)
            ->assertDontSee('Request to participate');

        // The workspace action is the existing selection flow.
        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $assigned))
            ->assertRedirect('/manager')
            ->assertSessionHas('active_tournament_id', $assigned->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Browsing grants nothing
    |--------------------------------------------------------------------------
    */

    public function test_viewing_creates_no_assignment_session_or_access(): void
    {
        $open = $this->open('Look Only Cup');
        $mine = $this->tournament('My Cup', '2026-11-05', '2026-11-25');
        $mine->users()->attach($this->manager->id);

        $pivotRows = DB::table('tournament_user')->count();

        // No selected tournament: viewing does not select one.
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSessionMissing('active_tournament_id');
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $open))
            ->assertOk()
            ->assertSessionMissing('active_tournament_id');

        // An existing selection is left untouched.
        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $mine->id])
            ->get(route('manager.open-tournaments.show', $open))
            ->assertOk()
            ->assertSessionHas('active_tournament_id', $mine->id);

        $this->assertSame($pivotRows, DB::table('tournament_user')->count());
        $this->assertDatabaseMissing('tournament_user', ['tournament_id' => $open->id, 'user_id' => $this->manager->id]);
        $this->assertSame([$mine->id], $this->manager->manageableTournaments()->pluck('tournaments.id')->all());

        // Workspace authorization is unchanged.
        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $open))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('My Cup')
            ->assertDontSee('Look Only Cup');

        foreach (['/manager', '/teams', '/teams/create', '/games', '/standings'] as $uri) {
            $this->actingAs($this->manager)
                ->withSession(['active_tournament_id' => $open->id])
                ->get($uri)
                ->assertRedirect(route('tournaments.index'));
        }

        $this->actingAs($this->manager)
            ->post(route('teams.store'), ['tournament_id' => $open->id, 'name' => 'Sneaky Five'])
            ->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseMissing('teams', ['name' => 'Sneaky Five']);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_only_team_managers_can_browse_open_tournaments(): void
    {
        $tournament = $this->open();
        $routes = [
            route('manager.open-tournaments.index'),
            route('manager.open-tournaments.show', $tournament),
        ];

        foreach ($routes as $uri) {
            $this->get($uri)->assertRedirect('/login');

            foreach (['admin', 'player'] as $role) {
                $this->actingAs(User::factory()->create(['role' => $role]))
                    ->get($uri)
                    ->assertForbidden();
            }

            auth()->logout();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public function test_manager_navigation_links_to_open_tournaments_only_for_managers(): void
    {
        $link = 'href="' . route('manager.open-tournaments.index') . '"';

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertSee($link, false)
            ->assertSee('Open Tournaments');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee($link, false);

        $this->actingAs(User::factory()->create(['role' => 'player']))
            ->get(route('player.teams.index'))
            ->assertOk()
            ->assertDontSee($link, false);
    }

    public function test_unassigned_manager_empty_state_links_to_open_tournaments(): void
    {
        $this->open();

        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('No Tournaments Available')
            ->assertSee('Browse open tournaments');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertDontSee('Browse open tournaments');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminTournamentEditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private Tournament $tournament;
    private Tournament $other;
    private Team $home;
    private Team $away;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Freeze the clock inside the tournament's Nov 1–30 run so its
        // date-derived status is "ongoing" (noon in Asia/Manila).
        $this->travelTo('2026-11-10 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);

        $this->tournament = Tournament::create([
            'name' => 'Original Cup',
            'type' => 'inter_barangay',
            'venue' => 'Original Court, Quezon City',
            'description' => 'Original description',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'status' => 'ongoing',
        ]);

        // A second ongoing tournament running at the same time.
        $this->other = Tournament::create(['name' => 'Concurrent League', 'status' => 'ongoing', 'venue' => 'Other Gym']);

        $this->tournament->users()->attach($this->manager->id);
        $this->other->users()->attach($this->manager->id);

        $this->home = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->manager->id, 'name' => 'Edit Home']);
        $this->away = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->manager->id, 'name' => 'Edit Away']);

        $this->game = Game::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => '2026-11-05 18:00:00',
            'venue' => 'Game-specific Court',
            'status' => 'scheduled',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Original Cup',
            'type' => 'inter_barangay',
            ...$this->formatFields(),
            'venue' => 'Original Court, Quezon City',
            'description' => 'Original description',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
        ], $overrides);
    }

    private function update(array $payload, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->put(route('admin.tournaments.update', $this->tournament), $payload);
    }

    /*
    |--------------------------------------------------------------------------
    | Listing & form
    |--------------------------------------------------------------------------
    */

    public function test_admin_list_has_an_edit_action_for_each_tournament(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.index'))
            ->assertOk()
            ->assertSee('href="' . route('admin.tournaments.edit', $this->tournament) . '"', false)
            ->assertSee('href="' . route('admin.tournaments.edit', $this->other) . '"', false);
    }

    public function test_edit_form_is_prefilled_with_current_values(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $this->tournament))
            ->assertOk()
            ->assertSee('action="' . route('admin.tournaments.update', $this->tournament) . '"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('value="Original Cup"', false)
            ->assertSee('value="Original Court, Quezon City"', false)
            ->assertSee('value="2026-11-01"', false)
            ->assertSee('value="2026-11-30"', false)
            ->assertSee('Original description')
            ->assertDontSee('name="status"', false)
            ->assertSee('<option value="inter_barangay" selected', false)
            // No manager assignment section on the edit form.
            ->assertDontSee('Assigned team managers')
            ->assertDontSee($this->manager->name);
    }

    /*
    |--------------------------------------------------------------------------
    | Successful updates
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_update_all_tournament_fields(): void
    {
        $this->update($this->payload([
            'name' => 'Renamed Cup',
            'type' => 'inter_town',
            'venue' => 'New Arena, Pasig',
            'description' => 'New description',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-20',
        ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHas('success', 'Tournament updated successfully.');

        $tournament = $this->tournament->fresh();

        $this->assertSame('Renamed Cup', $tournament->name);
        $this->assertSame('inter_town', $tournament->type);
        $this->assertSame('New Arena, Pasig', $tournament->venue);
        $this->assertSame('New description', $tournament->description);
        $this->assertSame('2026-12-01', $tournament->start_date->format('Y-m-d'));
        $this->assertSame('2026-12-20', $tournament->end_date->format('Y-m-d'));
        // Moving the dates into the future makes it upcoming again.
        $this->assertSame('upcoming', $tournament->status);
    }

    public function test_update_keeps_assignments_teams_games_and_other_tournaments_intact(): void
    {
        $otherBefore = $this->other->fresh()->toArray();

        $this->update($this->payload(['name' => 'Renamed Cup']))->assertRedirect();

        $tournament = $this->tournament->fresh();

        $this->assertSame($this->tournament->id, $tournament->id);
        $this->assertSame(2, Tournament::count());
        $this->assertSame([$this->manager->id], $tournament->users()->pluck('users.id')->all());
        $this->assertSame([$this->home->id, $this->away->id], $tournament->teams()->orderBy('id')->pluck('id')->all());
        $this->assertSame($this->tournament->id, $this->game->fresh()->tournament_id);
        $this->assertSame('Game-specific Court', $this->game->fresh()->venue);
        $this->assertEquals($otherBefore, $this->other->fresh()->toArray());

        // A manager who had it selected sees the new name immediately.
        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get('/manager')
            ->assertOk()
            ->assertSee('Renamed Cup');
    }

    public function test_tournament_type_is_saved_on_create_and_update(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tournaments.store'), [
            'name' => 'Typed Cup',
            'type' => 'inter_zone',
            ...$this->formatFields(),
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-20',
        ]);

        $this->assertSame('inter_zone', Tournament::where('name', 'Typed Cup')->value('type'));

        $this->update($this->payload(['type' => 'inter_zone']));
        $this->assertSame('inter_zone', $this->tournament->fresh()->type);
    }

    /*
    |--------------------------------------------------------------------------
    | Venue
    |--------------------------------------------------------------------------
    */

    public function test_venue_can_be_changed_and_is_shown_publicly(): void
    {
        $this->update($this->payload(['venue' => 'Marikina Sports Center']))->assertRedirect();

        $this->assertSame('Marikina Sports Center', $this->tournament->fresh()->venue);

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('Marikina Sports Center');
    }

    public function test_editing_unrelated_fields_preserves_the_venue(): void
    {
        // As submitted by the edit form (venue field prefilled).
        $this->update($this->payload(['description' => 'Only the description changed']))->assertRedirect();
        $this->assertSame('Original Court, Quezon City', $this->tournament->fresh()->venue);

        // A request that omits the venue field entirely also preserves it.
        $payload = $this->payload(['name' => 'Renamed Without Venue']);
        unset($payload['venue']);

        $this->update($payload)->assertRedirect();

        $this->assertSame('Renamed Without Venue', $this->tournament->fresh()->name);
        $this->assertSame('Original Court, Quezon City', $this->tournament->fresh()->venue);
    }

    public function test_blank_venue_clears_it_like_the_create_form(): void
    {
        $this->update($this->payload(['venue' => '']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($this->tournament->fresh()->venue);

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('To be announced');
    }

    /*
    |--------------------------------------------------------------------------
    | Validation (same rules as create)
    |--------------------------------------------------------------------------
    */

    public function test_invalid_input_is_rejected_and_nothing_changes(): void
    {
        $cases = [
            'name' => ['name' => ''],
            'type' => ['type' => 'world_cup'],
            'start_date' => ['start_date' => ''],
            'end_date' => ['start_date' => '2026-12-10', 'end_date' => '2026-12-01'],
            'venue' => ['venue' => str_repeat('v', 256)],
        ];

        foreach ($cases as $field => $overrides) {
            $this->actingAs($this->admin)
                ->from(route('admin.tournaments.edit', $this->tournament))
                ->put(route('admin.tournaments.update', $this->tournament), $this->payload($overrides + ['name' => 'Should Not Save']))
                ->assertRedirect(route('admin.tournaments.edit', $this->tournament))
                ->assertSessionHasErrors($field);
        }

        $tournament = $this->tournament->fresh();
        $this->assertSame('Original Cup', $tournament->name);
        $this->assertSame('Original Court, Quezon City', $tournament->venue);
        $this->assertSame('ongoing', $tournament->status);
    }

    public function test_venue_of_exactly_255_characters_is_accepted(): void
    {
        $venue = str_repeat('v', 255);

        $this->update($this->payload(['venue' => $venue]))->assertSessionHasNoErrors();

        $this->assertSame($venue, $this->tournament->fresh()->venue);
    }

    public function test_validation_errors_are_shown_on_the_edit_form_with_input_kept(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tournaments.edit', $this->tournament))
            ->followingRedirects()
            ->put(route('admin.tournaments.update', $this->tournament), $this->payload([
                'name' => 'Kept Name',
                'end_date' => '2026-10-01',
            ]))
            ->assertOk()
            ->assertSee('Edit')
            ->assertSee('value="Kept Name"', false)
            ->assertSee('The end date field must be a date after or equal to start date.');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization & missing tournaments
    |--------------------------------------------------------------------------
    */

    public function test_only_admins_can_edit_or_update(): void
    {
        $player = User::factory()->create(['role' => 'player']);

        foreach ([$this->manager, $player] as $user) {
            $this->actingAs($user)->get(route('admin.tournaments.edit', $this->tournament))->assertForbidden();
            $this->update($this->payload(['name' => 'Hijacked']), $user)->assertForbidden();
        }

        auth()->logout();

        $this->get(route('admin.tournaments.edit', $this->tournament))->assertRedirect('/login');
        $this->put(route('admin.tournaments.update', $this->tournament), $this->payload(['name' => 'Hijacked']))
            ->assertRedirect('/login');

        $this->assertSame('Original Cup', $this->tournament->fresh()->name);
    }

    public function test_nonexistent_tournament_returns_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/tournaments/999999/edit')->assertNotFound();
        $this->actingAs($this->admin)->put('/admin/tournaments/999999', $this->payload())->assertNotFound();

        $this->assertSame(2, Tournament::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Before the venue migration is applied
    |--------------------------------------------------------------------------
    */

    public function test_editing_works_before_the_venue_column_exists(): void
    {
        Schema::table('tournaments', fn ($table) => $table->dropColumn('venue'));
        $this->assertFalse(Tournament::supportsVenue());

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $this->tournament))
            ->assertOk()
            ->assertSee('value="Original Cup"', false)
            ->assertDontSee('name="venue"', false);

        $this->update($this->payload([
            'name' => 'Unmigrated Rename',
            'venue' => 'Ignored Venue',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-20',
        ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $tournament = $this->tournament->fresh();
        $this->assertSame('Unmigrated Rename', $tournament->name);
        $this->assertSame('upcoming', $tournament->status);
        $this->assertArrayNotHasKey('venue', $tournament->getAttributes());
    }
}

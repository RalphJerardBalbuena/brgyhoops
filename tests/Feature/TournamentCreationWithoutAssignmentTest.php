<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tournaments are open to all eligible managers through the application and
 * approval workflow: admins no longer assign managers when creating or
 * editing a tournament.
 */
class TournamentCreationWithoutAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager', 'name' => 'Marco Manager']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Open Season Cup',
            'type' => 'inter_barangay',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
        ], $overrides);
    }

    public function test_create_and_edit_forms_have_no_manager_assignment_or_status_block(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertDontSee('Assign team managers')
            ->assertDontSee('name="manager_ids[]"', false)
            ->assertDontSee('Marco Manager')
            ->assertDontSee('Set automatically from the dates')
            ->assertDontSee('name="status"', false);

        $tournament = Tournament::create($this->payload());
        $tournament->users()->attach($this->manager->id);

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $tournament))
            ->assertOk()
            ->assertDontSee('Assigned team managers')
            ->assertDontSee('name="manager_ids[]"', false)
            ->assertDontSee('Set automatically from the dates')
            ->assertDontSee('name="status"', false);
    }

    public function test_creating_a_tournament_assigns_no_managers_and_ignores_submitted_ones(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->payload([
                'prize_pool' => '25000',
                'manager_ids' => [$this->manager->id],
            ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Tournament created successfully. Team managers can apply to take part while it is upcoming.');

        $tournament = Tournament::where('name', 'Open Season Cup')->firstOrFail();

        // Dates, derived status and prize pool are saved as before.
        $this->assertSame('upcoming', $tournament->status);
        $this->assertSame('25000.00', $tournament->prize_pool);

        // No participation, no application, no access.
        $this->assertDatabaseCount('tournament_user', 0);
        $this->assertDatabaseCount('tournament_applications', 0);

        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $tournament))
            ->assertForbidden();

        // The tournament is open to every manager instead.
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertSee('Open Season Cup')
            ->assertDontSee('<span class="badge badge-dark">Assigned</span>', false);
    }

    public function test_invalid_manager_ids_no_longer_cause_validation_errors(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->payload(['manager_ids' => ['not-a-user', 999999]]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('tournament_user', 0);
    }

    public function test_date_and_prize_pool_validation_still_apply_on_create(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tournaments.create'))
            ->post(route('admin.tournaments.store'), $this->payload([
                'start_date' => '2026-11-20',
                'end_date' => '2026-11-10',
                'prize_pool' => '-5',
            ]))
            ->assertRedirect(route('admin.tournaments.create'))
            ->assertSessionHasErrors(['end_date', 'prize_pool']);

        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_editing_preserves_existing_participation_and_ignores_submitted_managers(): void
    {
        $other = User::factory()->create(['role' => 'team_manager']);
        $tournament = Tournament::create($this->payload());
        $tournament->users()->attach($this->manager->id); // existing participation

        $this->actingAs($this->admin)
            ->put(route('admin.tournaments.update', $tournament), $this->payload([
                'name' => 'Renamed Season Cup',
                'manager_ids' => [$other->id],
            ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Season Cup', $tournament->fresh()->name);
        $this->assertSame([$this->manager->id], $tournament->users()->pluck('users.id')->all());
    }

    public function test_managers_join_a_new_tournament_only_through_approval(): void
    {
        $this->actingAs($this->admin)->post(route('admin.tournaments.store'), $this->payload());
        $tournament = Tournament::where('name', 'Open Season Cup')->firstOrFail();

        // Apply: pending, no access.
        $this->actingAs($this->manager)
            ->post(route('manager.open-tournaments.apply', $tournament))
            ->assertSessionHas('success');

        $application = TournamentApplication::sole();
        $this->assertSame('pending', $application->status);
        $this->assertDatabaseCount('tournament_user', 0);

        // Duplicate refused.
        $this->actingAs($this->manager)
            ->post(route('manager.open-tournaments.apply', $tournament))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('tournament_applications', 1);

        // Approve: participation granted.
        $this->actingAs($this->admin)
            ->patch(route('admin.tournament-applications.approve', $application))
            ->assertSessionHas('success');

        $this->assertTrue(DB::table('tournament_user')->where([
            'tournament_id' => $tournament->id,
            'user_id' => $this->manager->id,
        ])->exists());

        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $tournament))
            ->assertRedirect('/manager');

        // A rejected manager gets nothing.
        $rejected = User::factory()->create(['role' => 'team_manager']);
        $this->actingAs($rejected)->post(route('manager.open-tournaments.apply', $tournament));
        $this->actingAs($this->admin)
            ->patch(route('admin.tournament-applications.reject', TournamentApplication::where('user_id', $rejected->id)->sole()))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tournament_user', ['user_id' => $rejected->id]);
    }

    public function test_overlap_rule_still_blocks_approval_for_a_new_tournament(): void
    {
        $current = Tournament::create($this->payload(['name' => 'Current League', 'start_date' => '2026-10-25', 'end_date' => '2026-11-15']));
        $current->users()->attach($this->manager->id);

        $this->actingAs($this->admin)->post(route('admin.tournaments.store'), $this->payload());
        $tournament = Tournament::where('name', 'Open Season Cup')->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('manager.open-tournaments.apply', $tournament))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('tournament_applications', 0);
        $this->assertDatabaseMissing('tournament_user', ['tournament_id' => $tournament->id]);
    }

    public function test_manager_empty_state_points_to_applications_not_the_administrator(): void
    {
        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('You are not taking part in any tournament yet.')
            ->assertSee('Browse open tournaments')
            ->assertDontSee('contact your administrator');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentApplication;
use App\Models\User;
use App\Support\TournamentApplications;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * S3.10: managers apply to open tournaments; admins approve or reject.
 * Only approval grants workspace access (a tournament_user row).
 */
class TournamentApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private Tournament $open;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);
        $this->open = $this->tournament('Open Cup', '2026-11-10', '2026-11-20');
    }

    private function tournament(string $name, string $start, string $end): Tournament
    {
        return Tournament::create([
            'name' => $name,
            'type' => 'inter_barangay',
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    private function apply(?Tournament $tournament = null, ?User $as = null, array $data = [])
    {
        return $this->actingAs($as ?? $this->manager)
            ->post(route('manager.open-tournaments.apply', $tournament ?? $this->open), $data);
    }

    private function pendingApplication(?User $manager = null, ?Tournament $tournament = null): TournamentApplication
    {
        return ($tournament ?? $this->open)->applications()->create(['user_id' => ($manager ?? $this->manager)->id]);
    }

    private function approve(TournamentApplication $application, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->patch(route('admin.tournament-applications.approve', $application));
    }

    private function reject(TournamentApplication $application, array $data = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)
            ->patch(route('admin.tournament-applications.reject', $application), $data);
    }

    private function assertNoWorkspaceAccess(User $manager, Tournament $tournament): void
    {
        $this->assertDatabaseMissing('tournament_user', ['tournament_id' => $tournament->id, 'user_id' => $manager->id]);
        $this->assertFalse($manager->manageableTournaments()->where('tournaments.id', $tournament->id)->exists());

        $this->actingAs($manager)
            ->post(route('tournaments.select', $tournament))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('tournaments.index'))
            ->assertDontSee($tournament->name);

        foreach (['/manager', '/teams', '/teams/create', '/games', '/standings'] as $uri) {
            $this->actingAs($manager)
                ->withSession(['active_tournament_id' => $tournament->id])
                ->get($uri)
                ->assertRedirect(route('tournaments.index'));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Submitting
    |--------------------------------------------------------------------------
    */

    public function test_manager_can_apply_to_an_open_tournament(): void
    {
        $this->apply()
            ->assertRedirect(route('manager.open-tournaments.show', $this->open))
            ->assertSessionHas('success')
            ->assertSessionMissing('active_tournament_id');

        $application = TournamentApplication::sole();
        $this->assertSame($this->open->id, $application->tournament_id);
        $this->assertSame($this->manager->id, $application->user_id);
        $this->assertSame('pending', $application->status);
        $this->assertNull($application->reviewed_at);

        $this->assertDatabaseCount('tournament_user', 0);
        $this->assertNoWorkspaceAccess($this->manager, $this->open);
    }

    public function test_manager_sees_pending_status_instead_of_the_request_button(): void
    {
        $this->apply();

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->open))
            ->assertOk()
            ->assertSee('<span class="badge badge-pending">Application pending</span>', false)
            ->assertSee('Application pending')
            ->assertDontSee('action="' . route('manager.open-tournaments.apply', $this->open) . '"', false);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertSee('<span class="badge badge-pending">Application pending</span>', false);
    }

    public function test_applying_is_refused_for_tournaments_that_are_not_open(): void
    {
        $ongoing = $this->tournament('Ongoing Cup', '2026-10-25', '2026-11-05');
        $completed = $this->tournament('Completed Cup', '2026-10-01', '2026-10-10');
        $legacyOngoing = Tournament::create(['name' => 'Legacy Ongoing', 'status' => 'ongoing']);

        foreach ([$ongoing, $completed, $legacyOngoing] as $tournament) {
            $this->apply($tournament)->assertNotFound();
        }

        $this->actingAs($this->manager)->post('/manager/open-tournaments/999999/apply')->assertNotFound();

        $this->assertDatabaseCount('tournament_applications', 0);
    }

    public function test_applications_close_at_manila_midnight_on_the_start_date(): void
    {
        $other = User::factory()->create(['role' => 'team_manager']);

        // 23:59:59 on Nov 9 in Manila: still open.
        $this->travelTo('2026-11-09 15:59:59');
        $this->apply()->assertSessionHas('success');

        // 00:00 on Nov 10 in Manila: started, no longer open.
        $this->travelTo('2026-11-09 16:00:00');
        $this->apply(null, $other)->assertNotFound();

        $this->assertSame([$this->manager->id], TournamentApplication::pluck('user_id')->all());
    }

    public function test_duplicate_applications_are_refused(): void
    {
        $this->apply()->assertSessionHas('success');

        $this->apply()
            ->assertRedirect(route('manager.open-tournaments.show', $this->open))
            ->assertSessionHas('error', TournamentApplications::DUPLICATE);

        $this->assertDatabaseCount('tournament_applications', 1);

        // Still a duplicate after rejection (one application per manager and tournament).
        $this->reject(TournamentApplication::sole());
        $this->apply()->assertSessionHas('error', TournamentApplications::DUPLICATE);

        $this->assertDatabaseCount('tournament_applications', 1);
        $this->assertSame('rejected', TournamentApplication::sole()->status);
    }

    public function test_the_database_enforces_one_application_per_manager_and_tournament(): void
    {
        $this->pendingApplication();

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('tournament_applications')->insert([
            'tournament_id' => $this->open->id,
            'user_id' => $this->manager->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_a_concurrent_duplicate_is_reported_instead_of_crashing(): void
    {
        // Simulate another request inserting the same application between the
        // eligibility check and this request's insert.
        TournamentApplication::creating(function (TournamentApplication $application) {
            DB::table('tournament_applications')->insert([
                'tournament_id' => $application->tournament_id,
                'user_id' => $application->user_id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertSame(
            [TournamentApplications::DUPLICATE],
            TournamentApplications::submit($this->manager, $this->open)
        );

        $this->assertDatabaseCount('tournament_user', 0);
    }

    public function test_assigned_managers_cannot_apply(): void
    {
        $this->open->users()->attach($this->manager->id);

        $this->apply()->assertSessionHas('error', 'You are already assigned to this tournament.');

        $this->assertDatabaseCount('tournament_applications', 0);
    }

    public function test_forged_user_and_status_fields_are_ignored(): void
    {
        $other = User::factory()->create(['role' => 'team_manager']);

        $this->apply(null, null, ['user_id' => $other->id, 'status' => 'approved'])
            ->assertSessionHas('success');

        $application = TournamentApplication::sole();
        $this->assertSame($this->manager->id, $application->user_id);
        $this->assertSame('pending', $application->status);
        $this->assertDatabaseCount('tournament_user', 0);
    }

    public function test_only_team_managers_can_apply(): void
    {
        $this->post(route('manager.open-tournaments.apply', $this->open))->assertRedirect('/login');

        foreach (['admin', 'player'] as $role) {
            $this->apply(null, User::factory()->create(['role' => $role]))->assertForbidden();
        }

        $this->assertDatabaseCount('tournament_applications', 0);
    }

    public function test_a_non_manager_is_refused_by_the_eligibility_check(): void
    {
        $player = User::factory()->create(['role' => 'player']);

        $this->assertSame(
            ['Only team managers can apply to tournaments.'],
            TournamentApplications::submit($player, $this->open)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approval grants access; pending and rejected do not
    |--------------------------------------------------------------------------
    */

    public function test_approval_grants_workspace_access(): void
    {
        $application = $this->pendingApplication();

        $this->approve($application)
            ->assertRedirect(route('admin.tournament-applications.index'))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame(1, DB::table('tournament_user')->where([
            'tournament_id' => $this->open->id,
            'user_id' => $this->manager->id,
        ])->count());

        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertSee('Open Cup');

        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $this->open))
            ->assertRedirect('/manager')
            ->assertSessionHas('active_tournament_id', $this->open->id);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->open))
            ->assertSee('You are assigned to this tournament')
            ->assertSee('<span class="badge badge-approved">Application approved</span>', false);
    }

    public function test_pending_and_rejected_applications_grant_no_access(): void
    {
        $pending = $this->pendingApplication();
        $this->assertNoWorkspaceAccess($this->manager, $this->open);

        $this->reject($pending, ['admin_review_note' => 'Roster too small.']);
        $this->assertSame('rejected', $pending->fresh()->status);
        $this->assertNoWorkspaceAccess($this->manager, $this->open);
    }

    /*
    |--------------------------------------------------------------------------
    | Approval and rejection recheck eligibility
    |--------------------------------------------------------------------------
    */

    public function test_an_application_can_only_be_reviewed_once(): void
    {
        $approved = $this->pendingApplication();
        $this->approve($approved);

        $this->approve($approved)->assertSessionHas('error', 'This application has already been reviewed.');
        $this->reject($approved, ['admin_review_note' => 'Too late'])->assertSessionHas('error', 'This application has already been reviewed.');

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertNull($approved->fresh()->admin_review_note);
        $this->assertDatabaseCount('tournament_user', 1);

        $other = User::factory()->create(['role' => 'team_manager']);
        $rejected = $this->pendingApplication($other);
        $this->reject($rejected);

        $this->approve($rejected)->assertSessionHas('error', 'This application has already been reviewed.');

        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertDatabaseMissing('tournament_user', ['user_id' => $other->id]);
    }

    public function test_approval_is_refused_once_the_tournament_has_started(): void
    {
        $application = $this->pendingApplication();

        $this->travelTo('2026-11-09 16:00:00'); // 00:00 on Nov 10 in Manila

        $this->approve($application)
            ->assertSessionHas('error', 'This tournament has already started, so the application can no longer be approved. Reject it instead.');

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertDatabaseCount('tournament_user', 0);

        // It can still be rejected.
        $this->reject($application)->assertSessionHas('success');
        $this->assertSame('rejected', $application->fresh()->status);
    }

    public function test_approval_is_refused_when_the_applicant_is_no_longer_a_manager(): void
    {
        $application = $this->pendingApplication();
        $this->manager->update(['role' => 'player']);

        $this->approve($application)
            ->assertSessionHas('error', 'The applicant is no longer a team manager. Reject the application instead.');

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertDatabaseCount('tournament_user', 0);
    }

    public function test_approving_a_manager_who_was_already_assigned_adds_no_duplicate_row(): void
    {
        $application = $this->pendingApplication();
        $this->open->users()->attach($this->manager->id);

        $this->approve($application)->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertDatabaseCount('tournament_user', 1);
    }

    public function test_rejection_reason_is_optional_and_shown_to_the_manager(): void
    {
        $withReason = $this->pendingApplication();

        $this->reject($withReason, ['admin_review_note' => 'Registration is full.'])
            ->assertRedirect(route('admin.tournament-applications.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Application rejected.');

        $this->assertSame('rejected', $withReason->fresh()->status);
        $this->assertSame('Registration is full.', $withReason->fresh()->admin_review_note);
        $this->assertNotNull($withReason->fresh()->reviewed_at);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->open))
            ->assertSee('<span class="badge badge-rejected">Application rejected</span>', false)
            ->assertSee('Reason: Registration is full.')
            ->assertDontSee('action="' . route('manager.open-tournaments.apply', $this->open) . '"', false);

        $other = User::factory()->create(['role' => 'team_manager']);
        $withoutReason = $this->pendingApplication($other);

        $this->reject($withoutReason, ['admin_review_note' => ''])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $withoutReason->fresh()->status);
        $this->assertNull($withoutReason->fresh()->admin_review_note);

        $this->actingAs($other)
            ->get(route('manager.open-tournaments.show', $this->open))
            ->assertSee('Application rejected')
            ->assertDontSee('Reason:');

        $this->assertDatabaseCount('tournament_user', 0);
    }

    public function test_rejection_reason_longer_than_1000_characters_is_refused(): void
    {
        $application = $this->pendingApplication();

        $this->actingAs($this->admin)
            ->from(route('admin.tournament-applications.index'))
            ->patch(route('admin.tournament-applications.reject', $application), ['admin_review_note' => str_repeat('x', 1001)])
            ->assertSessionHasErrors('admin_review_note');

        $this->assertSame('pending', $application->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin screen and authorization
    |--------------------------------------------------------------------------
    */

    public function test_admin_sees_applications_with_pending_first(): void
    {
        $rejectedManager = User::factory()->create(['role' => 'team_manager', 'name' => 'Rita Rejected']);
        $pendingManager = User::factory()->create(['role' => 'team_manager', 'name' => 'Paolo Pending']);

        $rejected = $this->pendingApplication($rejectedManager);
        $this->reject($rejected, ['admin_review_note' => 'Not eligible']);
        $pending = $this->pendingApplication($pendingManager);

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertOk()
            ->assertSeeInOrder(['Paolo Pending', 'Rita Rejected'])
            ->assertSee('Open Cup')
            ->assertSee('Review note: Not eligible')
            ->assertSee('action="' . route('admin.tournament-applications.approve', $pending) . '"', false)
            ->assertSee('action="' . route('admin.tournament-applications.reject', $pending) . '"', false)
            ->assertDontSee('action="' . route('admin.tournament-applications.approve', $rejected) . '"', false);
    }

    public function test_admin_screen_offers_only_rejection_for_a_started_tournament(): void
    {
        $application = $this->pendingApplication();
        $this->travelTo('2026-11-10 04:00:00');

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertOk()
            ->assertSee('This tournament has already started')
            ->assertDontSee('action="' . route('admin.tournament-applications.approve', $application) . '"', false)
            ->assertSee('action="' . route('admin.tournament-applications.reject', $application) . '"', false);
    }

    public function test_only_admins_can_view_or_review_applications(): void
    {
        $application = $this->pendingApplication();

        $this->get(route('admin.tournament-applications.index'))->assertRedirect('/login');
        $this->patch(route('admin.tournament-applications.approve', $application))->assertRedirect('/login');

        foreach (['team_manager', 'player'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.tournament-applications.index'))->assertForbidden();
            $this->approve($application, $user)->assertForbidden();
            $this->reject($application, [], $user)->assertForbidden();
        }

        // The applicant cannot approve their own application either.
        $this->approve($application, $this->manager)->assertForbidden();

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertDatabaseCount('tournament_user', 0);

        $this->actingAs($this->admin)->patch('/admin/tournament-applications/999999/approve')->assertNotFound();
    }

    public function test_managers_only_see_their_own_application_status(): void
    {
        $other = User::factory()->create(['role' => 'team_manager']);
        $this->pendingApplication($other);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->open))
            ->assertDontSee('Application pending')
            ->assertSee('action="' . route('manager.open-tournaments.apply', $this->open) . '"', false);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertDontSee('Application pending');
    }

    public function test_admin_navigation_links_to_manager_applications(): void
    {
        $link = 'href="' . route('admin.tournament-applications.index') . '"';

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee($link, false);
        $this->actingAs($this->manager)->get(route('manager.open-tournaments.index'))->assertDontSee($link, false);
    }

    /*
    |--------------------------------------------------------------------------
    | Separate from team roster applications
    |--------------------------------------------------------------------------
    */

    public function test_tournament_applications_are_separate_from_team_applications(): void
    {
        // Ends before Open Cup starts, so the S3.11 overlap rule does not apply.
        $assigned = $this->tournament('Running League', '2026-10-25', '2026-11-05');
        $assigned->users()->attach($this->manager->id);
        $team = Team::create(['tournament_id' => $assigned->id, 'user_id' => $this->manager->id, 'name' => 'Roster Five']);
        $team->forceFill(['application_status' => 'pending', 'applied_at' => now()])->save();

        $application = $this->pendingApplication();

        // Tournament applications do not show on the team review screen, and vice versa.
        $this->actingAs($this->admin)
            ->get(route('admin.team-applications.index'))
            ->assertSee('Roster Five')
            ->assertDontSee(route('admin.tournament-applications.approve', $application), false);

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertDontSee('Roster Five');

        // Reviewing one does not touch the other.
        $this->approve($application);
        $this->assertSame('pending', $team->fresh()->application_status);

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.reject', $team), ['admin_review_note' => 'Fix roster'])
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame('rejected', $team->fresh()->application_status);
    }
}

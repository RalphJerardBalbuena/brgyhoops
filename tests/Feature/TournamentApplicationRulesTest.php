<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentApplication;
use App\Models\User;
use App\Support\TournamentApplications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Manager application rules: one pending application at a time, and an
 * approval closes the manager's other overlapping pending applications as
 * "conflict" (kept, not deleted). Elimination still releases a manager.
 */
class TournamentApplicationRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private Tournament $a;
    private Tournament $b;
    private Tournament $c;
    private Tournament $d;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);

        $this->a = $this->tournament('A Cup', '2026-11-10', '2026-11-20');
        $this->b = $this->tournament('B Cup', '2026-11-15', '2026-11-25'); // overlaps A
        $this->c = $this->tournament('C Cup', '2026-11-21', '2026-11-30'); // starts the day after A ends
        $this->d = $this->tournament('D Cup', '2026-12-01', '2026-12-10'); // overlaps nothing above
    }

    private function tournament(string $name, string $start, string $end): Tournament
    {
        return Tournament::create(['name' => $name, 'type' => 'inter_barangay', 'start_date' => $start, 'end_date' => $end]);
    }

    private function apply(Tournament $tournament, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->manager)->post(route('manager.open-tournaments.apply', $tournament));
    }

    private function application(Tournament $tournament, ?User $manager = null): TournamentApplication
    {
        return TournamentApplication::where('tournament_id', $tournament->id)
            ->where('user_id', ($manager ?? $this->manager)->id)
            ->firstOrFail();
    }

    /**
     * A pending application created before the one-pending rule existed.
     */
    private function legacyPending(Tournament $tournament, ?User $manager = null): TournamentApplication
    {
        return $tournament->applications()->create(['user_id' => ($manager ?? $this->manager)->id]);
    }

    private function approve(TournamentApplication $application)
    {
        return $this->actingAs($this->admin)->patch(route('admin.tournament-applications.approve', $application));
    }

    private function reject(TournamentApplication $application)
    {
        return $this->actingAs($this->admin)->patch(route('admin.tournament-applications.reject', $application));
    }

    private function hasAccess(Tournament $tournament, ?User $manager = null): bool
    {
        return DB::table('tournament_user')->where([
            'tournament_id' => $tournament->id,
            'user_id' => ($manager ?? $this->manager)->id,
        ])->exists();
    }

    private function simulateConcurrentInsertAfterManagerLock(callable $insert): \Closure
    {
        $fired = false;

        DB::listen(function ($query) use (&$fired, $insert) {
            if (!$fired && str_contains($query->sql, 'from "users"') && str_contains($query->sql, '"users"."id" = ?')) {
                $fired = true;
                $insert();
            }
        });

        return function () use (&$fired) {
            return $fired;
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Rule 1: one pending application at a time
    |--------------------------------------------------------------------------
    */

    public function test_a_second_application_is_blocked_while_one_is_pending(): void
    {
        $this->apply($this->a)->assertSessionHas('success');

        $message = 'You already have a pending application for A Cup. You can apply to another tournament once an administrator has reviewed it.';

        // Blocked for a non-overlapping tournament as well as an overlapping one.
        $this->apply($this->d)
            ->assertRedirect(route('manager.open-tournaments.show', $this->d))
            ->assertSessionHas('error', $message);
        $this->apply($this->b)->assertSessionHas('error', $message);

        $this->assertDatabaseCount('tournament_applications', 1);

        // Another manager is unaffected.
        $other = User::factory()->create(['role' => 'team_manager']);
        $this->apply($this->d, $other)->assertSessionHas('success');
    }

    public function test_after_rejection_the_manager_may_apply_to_another_tournament_but_not_the_same_one(): void
    {
        $this->apply($this->a)->assertSessionHas('success');
        $this->reject($this->application($this->a))->assertSessionHas('success');

        // No longer pending, so a new application is allowed...
        $this->apply($this->d)->assertSessionHas('success');
        $this->assertSame('pending', $this->application($this->d)->status);

        // ...but the rejected tournament stays closed (one application per tournament).
        $this->reject($this->application($this->d));
        $this->apply($this->a)->assertSessionHas('error', TournamentApplications::DUPLICATE);
        $this->assertSame('rejected', $this->application($this->a)->status);
    }

    public function test_after_approval_the_manager_may_apply_to_a_non_overlapping_tournament(): void
    {
        $this->apply($this->a)->assertSessionHas('success');
        $this->approve($this->application($this->a))->assertSessionHas('success');

        // Overlapping B is still refused by the scheduling rule; C and D do not overlap A.
        $this->apply($this->b)->assertSessionHas('error');
        $this->apply($this->c)->assertSessionHas('success');
        $this->approve($this->application($this->c))->assertSessionHas('success');

        $this->assertTrue($this->hasAccess($this->a));
        $this->assertTrue($this->hasAccess($this->c));
        $this->assertFalse($this->hasAccess($this->b));
    }

    /*
    |--------------------------------------------------------------------------
    | Rules 2-4: approval closes overlapping pending applications
    |--------------------------------------------------------------------------
    */

    public function test_approval_closes_overlapping_pending_applications_and_keeps_non_overlapping_ones(): void
    {
        $sharedDay = $this->tournament('Shared Day Cup', '2026-11-20', '2026-11-22'); // starts on A's end day
        $aApp = $this->legacyPending($this->a);
        $bApp = $this->legacyPending($this->b);
        $sharedApp = $this->legacyPending($sharedDay);
        $cApp = $this->legacyPending($this->c);

        $otherManager = User::factory()->create(['role' => 'team_manager']);
        $othersB = $this->legacyPending($this->b, $otherManager);

        $this->approve($aApp)->assertSessionHas('success');

        $this->assertSame('approved', $aApp->fresh()->status);
        $this->assertTrue($this->hasAccess($this->a));

        foreach ([$bApp, $sharedApp] as $closed) {
            $closed->refresh();
            $this->assertSame('conflict', $closed->status);
            $this->assertNotNull($closed->reviewed_at);
            $this->assertSame('Closed automatically: it overlaps A Cup, which was approved for this manager.', $closed->admin_review_note);
        }

        // Non-overlapping and other managers' applications are untouched; nothing is deleted.
        $this->assertSame('pending', $cApp->fresh()->status);
        $this->assertSame('pending', $othersB->fresh()->status);
        $this->assertDatabaseCount('tournament_applications', 5);
        $this->assertFalse($this->hasAccess($this->b));
        $this->assertFalse($this->hasAccess($sharedDay));
    }

    public function test_approval_is_still_refused_when_participation_conflicts(): void
    {
        $this->apply($this->b)->assertSessionHas('success');

        // Assigned to overlapping A afterwards (e.g. a legacy assignment).
        $this->a->users()->attach($this->manager->id);

        $this->approve($this->application($this->b))->assertSessionHas('error');
        $this->assertSame('pending', $this->application($this->b)->status);
        $this->assertFalse($this->hasAccess($this->b));
    }

    public function test_conflict_applications_are_shown_and_do_not_count_as_pending(): void
    {
        $aApp = $this->legacyPending($this->a);
        $bApp = $this->legacyPending($this->b);
        $this->approve($aApp);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->b))
            ->assertOk()
            ->assertSee('<span class="badge badge-cancelled">Schedule conflict</span>', false)
            ->assertSee('Application closed: schedule conflict')
            ->assertSee('Reason: Closed automatically: it overlaps A Cup, which was approved for this manager.')
            // Still taking part in A: no resubmit action, the reason instead.
            ->assertDontSee('Apply again')
            ->assertDontSee('action="' . route('manager.open-tournaments.apply', $this->b) . '"', false)
            ->assertSee('You cannot apply again yet.')
            ->assertSee('You are still taking part in A Cup, which overlaps these dates.');

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertOk()
            ->assertSee('Schedule conflict')
            ->assertDontSee('action="' . route('admin.tournament-applications.approve', $bApp) . '"', false)
            ->assertDontSee('action="' . route('admin.tournament-applications.reject', $bApp) . '"', false);

        // The closed application is not pending, so another (non-overlapping) one is allowed.
        $this->apply($this->d)->assertSessionHas('success');
    }

    /*
    |--------------------------------------------------------------------------
    | Rule 5: elimination
    |--------------------------------------------------------------------------
    */

    public function test_a_conflict_closed_application_can_be_resubmitted_after_elimination(): void
    {
        $aApp = $this->legacyPending($this->a);
        $bApp = $this->legacyPending($this->b);
        $this->approve($aApp);
        $this->assertSame('conflict', $bApp->fresh()->status);

        // Still taking part in A: resubmitting B is refused.
        $this->apply($this->b)->assertSessionHas('error');
        $this->assertSame('conflict', $bApp->fresh()->status);

        // A is under way; the manager's only approved team there is eliminated.
        $this->travelTo('2026-11-12 04:00:00');
        $team = Team::create(['tournament_id' => $this->a->id, 'user_id' => $this->manager->id, 'name' => 'A Five']);
        $team->forceFill(['application_status' => 'approved'])->save();

        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $this->a->id])
            ->patch(route('standings.teams.eliminate', $team))
            ->assertSessionHas('success');

        // Elimination approves nothing by itself.
        $this->assertSame('conflict', $bApp->fresh()->status);
        $this->assertFalse($this->hasAccess($this->b));

        // Resubmission reopens the same application as pending.
        $this->apply($this->b)->assertSessionHas('success');
        $bApp->refresh();
        $this->assertSame('pending', $bApp->status);
        $this->assertNull($bApp->reviewed_at);
        $this->assertNull($bApp->admin_review_note);
        $this->assertSame(1, TournamentApplication::where('tournament_id', $this->b->id)->where('user_id', $this->manager->id)->count());
        $this->assertFalse($this->hasAccess($this->b));

        // Approval is still required, and rechecks eligibility.
        $this->approve($bApp)->assertSessionHas('success');
        $this->assertTrue($this->hasAccess($this->b));
    }

    public function test_apply_again_is_offered_only_once_eligibility_is_restored(): void
    {
        $aApp = $this->legacyPending($this->a);
        $bApp = $this->legacyPending($this->b);
        $this->approve($aApp);

        $applyAction = 'action="' . route('manager.open-tournaments.apply', $this->b) . '"';

        // Conflict unresolved: no resubmit action, an explanation instead.
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->b))
            ->assertOk()
            ->assertDontSee($applyAction, false)
            ->assertDontSee('Apply again')
            ->assertSee('You cannot apply again yet.')
            ->assertSee('You are still taking part in A Cup, which overlaps these dates.');

        // The backend stays authoritative: a forged submission is refused.
        $this->apply($this->b)->assertSessionHas('error');
        $this->assertSame('conflict', $bApp->fresh()->status);

        // Released by official elimination: the action appears.
        $this->travelTo('2026-11-12 04:00:00');
        $team = Team::create(['tournament_id' => $this->a->id, 'user_id' => $this->manager->id, 'name' => 'A Five']);
        $team->forceFill(['application_status' => 'approved'])->save();
        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $this->a->id])
            ->patch(route('standings.teams.eliminate', $team))
            ->assertSessionHas('success');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->b))
            ->assertOk()
            ->assertSee($applyAction, false)
            ->assertSee('Apply again')
            ->assertSee('You are eligible again')
            ->assertDontSee('You cannot apply again yet.');

        // Another pending application also withholds it (one pending at a time).
        $this->apply($this->d)->assertSessionHas('success');

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $this->b))
            ->assertDontSee($applyAction, false)
            ->assertSee('You cannot apply again yet.')
            ->assertSee('You already have a pending application for D Cup.');

        // Viewing the page changed nothing.
        $this->assertSame('conflict', $bApp->fresh()->status);
    }

    public function test_elimination_allows_a_new_overlapping_application_but_approves_nothing(): void
    {
        $current = $this->tournament('Current League', '2026-10-25', '2026-11-30');
        $current->users()->attach($this->manager->id);
        $team = Team::create(['tournament_id' => $current->id, 'user_id' => $this->manager->id, 'name' => 'Current Five']);
        $team->forceFill(['application_status' => 'approved'])->save();

        $this->apply($this->a)->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $current->id])
            ->patch(route('standings.teams.eliminate', $team))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('tournament_applications', 0);

        $this->apply($this->a)->assertSessionHas('success');
        $this->assertSame('pending', $this->application($this->a)->status);
        $this->assertFalse($this->hasAccess($this->a));

        // Still only one pending application at a time.
        $this->apply($this->d)->assertSessionHas('error');

        $this->approve($this->application($this->a))->assertSessionHas('success');
        $this->assertTrue($this->hasAccess($this->a));
    }

    /*
    |--------------------------------------------------------------------------
    | Concurrency safeguards
    |--------------------------------------------------------------------------
    */

    public function test_a_pending_application_committed_during_submission_blocks_it(): void
    {
        // Another request commits a pending application right after this
        // submission takes the manager lock, before its checks run.
        $fired = $this->simulateConcurrentInsertAfterManagerLock(function () {
            DB::table('tournament_applications')->insert([
                'tournament_id' => $this->d->id,
                'user_id' => $this->manager->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $problems = TournamentApplications::submit($this->manager, $this->a);

        $this->assertTrue($fired());
        $this->assertSame(['You already have a pending application for D Cup. You can apply to another tournament once an administrator has reviewed it.'], $problems);
        $this->assertDatabaseMissing('tournament_applications', ['tournament_id' => $this->a->id]);
    }

    public function test_an_overlapping_application_committed_during_approval_is_closed_by_it(): void
    {
        $aApp = $this->legacyPending($this->a);

        // Another request commits an overlapping pending application right
        // after this approval takes the manager lock.
        $fired = $this->simulateConcurrentInsertAfterManagerLock(function () {
            DB::table('tournament_applications')->insert([
                'tournament_id' => $this->b->id,
                'user_id' => $this->manager->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->assertSame([], TournamentApplications::approve($aApp));
        $this->assertTrue($fired());

        $this->assertSame('approved', $aApp->fresh()->status);
        $this->assertSame('conflict', $this->application($this->b)->status);
        $this->assertFalse($this->hasAccess($this->b));
    }

    public function test_an_application_reviewed_concurrently_is_not_closed_again(): void
    {
        $aApp = $this->legacyPending($this->a);
        $bApp = $this->legacyPending($this->b);

        // B is rejected by another admin right after this approval locks the manager.
        $fired = $this->simulateConcurrentInsertAfterManagerLock(function () use ($bApp) {
            DB::table('tournament_applications')->where('id', $bApp->id)->update([
                'status' => 'rejected',
                'admin_review_note' => 'Rejected by another admin',
            ]);
        });

        $this->assertSame([], TournamentApplications::approve($aApp));
        $this->assertTrue($fired());

        // The conflict close only touches applications that are still pending.
        $this->assertSame('rejected', $bApp->fresh()->status);
        $this->assertSame('Rejected by another admin', $bApp->fresh()->admin_review_note);
    }
}

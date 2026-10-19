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
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * S3.11: a manager may not take part in overlapping tournaments (inclusive
 * dates) while still active in one, unless all their approved teams there
 * were officially eliminated. Checked on submission and again on approval.
 */
class TournamentParticipationRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private Tournament $target;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);
        $this->target = $this->tournament('Target Cup', '2026-11-10', '2026-11-20');
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

    private function participating(string $name, string $start, string $end, ?User $manager = null): Tournament
    {
        $tournament = $this->tournament($name, $start, $end);
        $tournament->users()->attach(($manager ?? $this->manager)->id);

        return $tournament;
    }

    private function team(Tournament $tournament, string $status = 'approved', ?User $manager = null): Team
    {
        $team = Team::create([
            'tournament_id' => $tournament->id,
            'user_id' => ($manager ?? $this->manager)->id,
            'name' => 'Team ' . Str::random(6),
        ]);
        $team->forceFill(['application_status' => $status])->save();

        return $team;
    }

    private function apply(?Tournament $tournament = null, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->manager)
            ->post(route('manager.open-tournaments.apply', $tournament ?? $this->target));
    }

    private function application(?Tournament $tournament = null, ?User $manager = null): TournamentApplication
    {
        return TournamentApplication::where('tournament_id', ($tournament ?? $this->target)->id)
            ->where('user_id', ($manager ?? $this->manager)->id)
            ->firstOrFail();
    }

    private function approve(TournamentApplication $application)
    {
        return $this->actingAs($this->admin)
            ->patch(route('admin.tournament-applications.approve', $application));
    }

    private function eliminate(Team $team)
    {
        return $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $team->tournament_id])
            ->patch(route('standings.teams.eliminate', $team));
    }

    private function assertHasAccess(Tournament $tournament, bool $expected, ?User $manager = null): void
    {
        $this->assertSame($expected, DB::table('tournament_user')->where([
            'tournament_id' => $tournament->id,
            'user_id' => ($manager ?? $this->manager)->id,
        ])->exists());
    }

    /*
    |--------------------------------------------------------------------------
    | Overlap at submission
    |--------------------------------------------------------------------------
    */

    public function test_overlapping_participation_blocks_submission(): void
    {
        $this->participating('Current League', '2026-10-25', '2026-11-15');

        $this->apply()
            ->assertRedirect(route('manager.open-tournaments.show', $this->target))
            ->assertSessionHas('error', 'You are still taking part in Current League, which overlaps these dates. You can apply once your team has been eliminated there or that tournament has ended.');

        $this->assertDatabaseCount('tournament_applications', 0);
        $this->assertHasAccess($this->target, false);
    }

    public function test_shared_start_or_end_days_count_as_overlapping(): void
    {
        $cases = [
            'ends on the start day' => ['2026-11-02', '2026-11-10'],
            'starts on the end day' => ['2026-11-20', '2026-11-25'],
            'same single day' => ['2026-11-10', '2026-11-10'],
            'contains the range' => ['2026-11-05', '2026-11-30'],
            'inside the range' => ['2026-11-12', '2026-11-14'],
        ];

        foreach ($cases as $label => [$start, $end]) {
            $manager = User::factory()->create(['role' => 'team_manager']);
            $this->participating("Other {$label}", $start, $end, $manager);

            $this->apply(null, $manager)->assertSessionHas('error');

            $this->assertDatabaseMissing('tournament_applications', ['user_id' => $manager->id]);
        }
    }

    public function test_non_overlapping_participation_does_not_block(): void
    {
        $this->participating('Day Before League', '2026-11-02', '2026-11-09');
        $this->participating('Day After League', '2026-11-21', '2026-11-30');
        $this->participating('Finished League', '2026-10-01', '2026-10-10');
        // Legacy tournaments without dates cannot be compared and never conflict.
        Tournament::create(['name' => 'Undated League', 'status' => 'ongoing'])->users()->attach($this->manager->id);

        $this->apply()->assertSessionHas('success');
        $this->approve($this->application())->assertSessionHas('success');

        $this->assertHasAccess($this->target, true);
    }

    public function test_the_rule_applies_per_manager(): void
    {
        $busy = User::factory()->create(['role' => 'team_manager']);
        $this->participating('Busy League', '2026-11-05', '2026-11-15', $busy);

        // Another manager's participation does not affect this manager.
        $this->apply()->assertSessionHas('success');
        $this->approve($this->application())->assertSessionHas('success');

        // Several managers can take part in the same and in concurrent tournaments.
        $colleague = User::factory()->create(['role' => 'team_manager']);
        $this->apply(null, $colleague)->assertSessionHas('success');
        $this->approve($this->application(null, $colleague))->assertSessionHas('success');

        $this->assertHasAccess($this->target, true);
        $this->assertHasAccess($this->target, true, $colleague);

        // Only the manager who is busy elsewhere is blocked.
        $this->apply(null, $busy)->assertSessionHas('error');
        $this->assertHasAccess($this->target, false, $busy);
    }

    /*
    |--------------------------------------------------------------------------
    | Recheck at approval
    |--------------------------------------------------------------------------
    */

    public function test_two_pending_overlapping_applications_cannot_both_be_approved(): void
    {
        $overlapping = $this->tournament('Overlapping Cup', '2026-11-15', '2026-11-25');

        // Pending applications are not participation, so both can be submitted...
        $this->apply()->assertSessionHas('success');
        $this->apply($overlapping)->assertSessionHas('success');

        // ...but once one is approved, the other cannot be.
        $this->approve($this->application())->assertSessionHas('success');

        $this->approve($this->application($overlapping))
            ->assertSessionHas('error', "{$this->manager->name} is still taking part in Target Cup, which overlaps this tournament. Approve only after their team has been eliminated there, or reject the application.");

        $this->assertSame('pending', $this->application($overlapping)->status);
        $this->assertHasAccess($overlapping, false);

        $this->actingAs($this->admin)
            ->patch(route('admin.tournament-applications.reject', $this->application($overlapping)))
            ->assertSessionHas('success');
    }

    public function test_approval_rechecks_dates_changed_after_submission(): void
    {
        $current = $this->participating('Current League', '2026-10-25', '2026-11-09');

        $this->apply()->assertSessionHas('success');

        // The admin extends the current tournament so it now overlaps.
        $this->actingAs($this->admin)
            ->put(route('admin.tournaments.update', $current), [
                'name' => 'Current League',
                'type' => 'inter_barangay',
                'start_date' => '2026-10-25',
                'end_date' => '2026-11-12',
            ])
            ->assertSessionHasNoErrors();

        $this->approve($this->application())->assertSessionHas('error');

        $this->assertSame('pending', $this->application()->status);
        $this->assertHasAccess($this->target, false);
    }

    public function test_approval_rechecks_participation_gained_after_submission(): void
    {
        $this->apply()->assertSessionHas('success');

        // Assigned to an overlapping tournament after applying (e.g. at its creation).
        $this->participating('New League', '2026-11-18', '2026-11-28');

        $this->approve($this->application())->assertSessionHas('error');
        $this->assertHasAccess($this->target, false);
    }

    public function test_admin_screen_explains_a_conflict_and_offers_only_rejection(): void
    {
        $this->apply()->assertSessionHas('success');
        $this->participating('New League', '2026-11-18', '2026-11-28');
        $application = $this->application();

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertOk()
            ->assertSee('is still taking part in New League')
            ->assertDontSee('action="' . route('admin.tournament-applications.approve', $application) . '"', false)
            ->assertSee('action="' . route('admin.tournament-applications.reject', $application) . '"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Undated (legacy) target tournaments
    |--------------------------------------------------------------------------
    */

    private function undatedOpen(string $name = 'Legacy Open'): Tournament
    {
        // A legacy record: no dates, so its stored "upcoming" status keeps it open.
        $tournament = Tournament::create(['name' => $name, 'type' => 'inter_barangay', 'status' => 'upcoming']);
        $this->assertTrue($tournament->isOpenForApplications());

        return $tournament;
    }

    public function test_undated_target_cannot_bypass_the_rule_at_submission(): void
    {
        $legacy = $this->undatedOpen();
        $this->participating('Current League', '2026-10-25', '2026-11-30');

        $this->apply($legacy)
            ->assertRedirect(route('manager.open-tournaments.show', $legacy))
            ->assertSessionHas('error', 'You are still taking part in Current League. This tournament has no dates set yet, so it may overlap. You can apply once your team has been eliminated there or that tournament has ended.');

        $this->assertDatabaseCount('tournament_applications', 0);
        $this->assertHasAccess($legacy, false);

        // Upcoming participation (not started yet) also counts, as an overlap cannot be ruled out.
        $other = User::factory()->create(['role' => 'team_manager']);
        $this->participating('Future League', '2027-03-01', '2027-03-10', $other);
        $this->apply($legacy, $other)->assertSessionHas('error');

        // So does a legacy undated participation still marked ongoing.
        $third = User::factory()->create(['role' => 'team_manager']);
        Tournament::create(['name' => 'Legacy Running', 'status' => 'ongoing'])->users()->attach($third->id);
        $this->apply($legacy, $third)->assertSessionHas('error');

        $this->assertDatabaseCount('tournament_applications', 0);
    }

    public function test_undated_target_cannot_bypass_the_rule_at_approval(): void
    {
        $legacy = $this->undatedOpen();

        // No participation when applying...
        $this->apply($legacy)->assertSessionHas('success');

        // ...but assigned to another tournament before the admin reviews it.
        $this->participating('New League', '2026-12-01', '2026-12-10');

        $this->approve($this->application($legacy))
            ->assertSessionHas('error', "{$this->manager->name} is still taking part in New League. This tournament has no dates set yet, so it may overlap. Approve only after their team has been eliminated there, or reject the application.");

        $this->assertSame('pending', $this->application($legacy)->status);
        $this->assertHasAccess($legacy, false);

        $this->actingAs($this->admin)
            ->get(route('admin.tournament-applications.index'))
            ->assertSee('This tournament has no dates set yet, so it may overlap.')
            ->assertDontSee('action="' . route('admin.tournament-applications.approve', $this->application($legacy)) . '"', false);
    }

    public function test_undated_target_still_accepts_managers_without_active_participation(): void
    {
        $legacy = $this->undatedOpen();

        // Free manager.
        $this->apply($legacy)->assertSessionHas('success');
        $this->approve($this->application($legacy))->assertSessionHas('success');
        $this->assertHasAccess($legacy, true);

        // Participation that has ended does not block.
        $finished = User::factory()->create(['role' => 'team_manager']);
        $this->participating('Finished League', '2026-10-01', '2026-10-10', $finished);
        Tournament::create(['name' => 'Legacy Done', 'status' => 'completed'])->users()->attach($finished->id);
        $this->apply($legacy, $finished)->assertSessionHas('success');
        $this->approve($this->application($legacy, $finished))->assertSessionHas('success');

        // Elimination still releases the manager.
        $released = User::factory()->create(['role' => 'team_manager']);
        $current = $this->participating('Current League', '2026-10-25', '2026-11-30', $released);
        $team = $this->team($current, 'approved', $released);
        $this->apply($legacy, $released)->assertSessionHas('error');

        $this->eliminate($team)->assertSessionHas('success');
        $this->apply($legacy, $released)->assertSessionHas('success');
        $this->approve($this->application($legacy, $released))->assertSessionHas('success');
        $this->assertHasAccess($legacy, true, $released);
    }

    public function test_undated_target_check_follows_the_manila_date(): void
    {
        $legacy = $this->undatedOpen();
        $this->participating('Ending League', '2026-10-25', '2026-11-03');

        // 23:59:59 on the end day in Manila: still taking part.
        $this->travelTo('2026-11-03 15:59:59');
        $this->apply($legacy)->assertSessionHas('error');

        // 00:00 the next day in Manila: that tournament is completed.
        $this->travelTo('2026-11-03 16:00:00');
        $this->apply($legacy)->assertSessionHas('success');
    }

    /*
    |--------------------------------------------------------------------------
    | Elimination
    |--------------------------------------------------------------------------
    */

    public function test_elimination_before_the_end_releases_the_manager_but_approves_nothing(): void
    {
        $current = $this->participating('Current League', '2026-10-25', '2026-11-30');
        $team = $this->team($current);

        $this->apply()->assertSessionHas('error');

        $this->eliminate($team)
            ->assertRedirect(route('standings.index'))
            ->assertSessionHas('success', "{$team->name} has been marked as eliminated.");

        $team->refresh();
        $this->assertTrue($team->isEliminated());
        $this->assertSame('2026-11-01 04:00:00', $team->eliminated_at->format('Y-m-d H:i:s'));

        // Elimination grants nothing by itself.
        $this->assertDatabaseCount('tournament_applications', 0);
        $this->assertHasAccess($this->target, false);
        $this->assertHasAccess($current, true);

        // The manager may now apply, and still needs admin approval.
        $this->apply()->assertSessionHas('success');
        $this->assertSame('pending', $this->application()->status);
        $this->assertHasAccess($this->target, false);

        $this->approve($this->application())->assertSessionHas('success');
        $this->assertHasAccess($this->target, true);
    }

    public function test_elimination_after_submission_allows_a_refused_approval(): void
    {
        $current = $this->participating('Current League', '2026-10-25', '2026-11-09');
        $team = $this->team($current);

        $this->apply()->assertSessionHas('success');

        $current->update(['end_date' => '2026-11-12']);
        $this->approve($this->application())->assertSessionHas('error');

        $this->eliminate($team)->assertSessionHas('success');

        $this->approve($this->application())->assertSessionHas('success');
        $this->assertHasAccess($this->target, true);
    }

    public function test_manager_stays_active_until_every_approved_team_is_eliminated(): void
    {
        $current = $this->participating('Current League', '2026-10-25', '2026-11-30');
        $first = $this->team($current);
        $second = $this->team($current);
        $this->team($current, 'pending'); // unapproved teams do not count

        $this->eliminate($first)->assertSessionHas('success');
        $this->apply()->assertSessionHas('error');

        $this->eliminate($second)->assertSessionHas('success');
        $this->apply()->assertSessionHas('success');
    }

    public function test_participation_without_an_eliminated_approved_team_still_counts(): void
    {
        $current = $this->participating('Current League', '2026-10-25', '2026-11-30');

        // Assigned with no team at all.
        $this->apply()->assertSessionHas('error');

        // An eliminated timestamp on an unapproved team does not release the manager.
        $this->team($current, 'rejected')->forceFill(['eliminated_at' => now()])->save();
        $this->apply()->assertSessionHas('error');

        $this->assertFalse(TournamentApplications::isReleasedFrom($this->manager, $current));
    }

    public function test_admin_can_only_eliminate_approved_active_teams_in_an_ongoing_selected_tournament(): void
    {
        $ongoing = $this->participating('Current League', '2026-10-25', '2026-11-30');

        $pending = $this->team($ongoing, 'pending');
        $this->eliminate($pending)->assertSessionHas('error', 'Only approved teams can be eliminated.');
        $this->assertNull($pending->fresh()->eliminated_at);

        $approved = $this->team($ongoing);
        $this->eliminate($approved)->assertSessionHas('success');
        $firstTimestamp = $approved->fresh()->eliminated_at;

        $this->travelTo('2026-11-02 04:00:00');
        $this->eliminate($approved)->assertSessionHas('error', "{$approved->name} has already been eliminated.");
        $this->assertEquals($firstTimestamp, $approved->fresh()->eliminated_at);

        // Not yet started.
        $upcomingTeam = $this->team($this->target);
        $this->eliminate($upcomingTeam)
            ->assertSessionHas('error', 'Teams can only be eliminated while the tournament is ongoing.');
        $this->assertNull($upcomingTeam->fresh()->eliminated_at);

        // A team outside the selected tournament.
        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $ongoing->id])
            ->patch(route('standings.teams.eliminate', $upcomingTeam))
            ->assertForbidden();

        // A completed tournament cannot be selected, so its teams cannot be eliminated.
        $finished = $this->tournament('Finished League', '2026-10-01', '2026-10-10');
        $finishedTeam = $this->team($finished);
        $this->eliminate($finishedTeam)->assertRedirect(route('tournaments.index'));
        $this->assertNull($finishedTeam->fresh()->eliminated_at);
    }

    public function test_only_admins_can_eliminate_teams(): void
    {
        $ongoing = $this->participating('Current League', '2026-10-25', '2026-11-30');
        $team = $this->team($ongoing);

        $this->patch(route('standings.teams.eliminate', $team))->assertRedirect('/login');

        foreach ([$this->manager, User::factory()->create(['role' => 'player'])] as $user) {
            $this->actingAs($user)
                ->withSession(['active_tournament_id' => $ongoing->id])
                ->patch(route('standings.teams.eliminate', $team))
                ->assertForbidden();
        }

        $this->assertNull($team->fresh()->eliminated_at);
    }

    public function test_standings_show_elimination_with_the_action_for_admins_only(): void
    {
        $ongoing = $this->participating('Current League', '2026-10-25', '2026-11-30');
        $active = $this->team($ongoing);
        $out = $this->team($ongoing);
        $this->eliminate($out);

        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $ongoing->id])
            ->get(route('standings.index'))
            ->assertOk()
            ->assertSee('<span class="badge badge-cancelled">Eliminated</span>', false)
            ->assertSee('action="' . route('standings.teams.eliminate', $active) . '"', false)
            ->assertDontSee('action="' . route('standings.teams.eliminate', $out) . '"', false);

        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $ongoing->id])
            ->get(route('standings.index'))
            ->assertOk()
            ->assertSee('<span class="badge badge-cancelled">Eliminated</span>', false)
            ->assertDontSee('Mark eliminated');
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicates and concurrency
    |--------------------------------------------------------------------------
    */

    public function test_duplicates_are_still_refused_alongside_the_overlap_rule(): void
    {
        $this->apply()->assertSessionHas('success');
        $this->apply()->assertSessionHas('error', TournamentApplications::DUPLICATE);

        $this->assertDatabaseCount('tournament_applications', 1);
    }

    public function test_participation_committed_during_an_approval_is_seen_by_its_recheck(): void
    {
        $overlapping = $this->tournament('Overlapping Cup', '2026-11-15', '2026-11-25');
        $this->apply()->assertSessionHas('success');
        $application = $this->application();

        // Simulate another approval committing (an overlapping tournament_user row)
        // right after this approval takes the manager lock, before its recheck.
        $fired = false;
        DB::listen(function ($query) use (&$fired, $overlapping) {
            if (!$fired && str_contains($query->sql, 'from "users"') && str_contains($query->sql, '"users"."id" = ?')) {
                $fired = true;
                DB::table('tournament_user')->insert([
                    'tournament_id' => $overlapping->id,
                    'user_id' => $this->manager->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->assertNotEmpty(TournamentApplications::approve($application));
        $this->assertTrue($fired);

        $this->assertSame('pending', $application->fresh()->status);
        $this->assertHasAccess($this->target, false);
    }

    public function test_participation_committed_during_a_submission_is_seen_by_its_check(): void
    {
        $overlapping = $this->tournament('Overlapping Cup', '2026-11-15', '2026-11-25');

        $fired = false;
        DB::listen(function ($query) use (&$fired, $overlapping) {
            if (!$fired && str_contains($query->sql, 'from "users"') && str_contains($query->sql, '"users"."id" = ?')) {
                $fired = true;
                DB::table('tournament_user')->insert([
                    'tournament_id' => $overlapping->id,
                    'user_id' => $this->manager->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $problems = TournamentApplications::submit($this->manager, $this->target);

        $this->assertTrue($fired);
        $this->assertStringContainsString('Overlapping Cup', implode(' ', $problems));
        $this->assertDatabaseCount('tournament_applications', 0);
    }
}

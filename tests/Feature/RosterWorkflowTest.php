<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\TeamApplicationReviewed;
use App\Notifications\TeamApplicationSubmitted;
use App\Support\RosterRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Full workflow: admin creates tournament → manager creates team → players
 * request to join → manager approves (roster) → manager submits → admin
 * approves/rejects → only approved teams can be scheduled.
 */
class RosterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $rival;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // Small explicit limits keep the tests readable.
        config(['brgyhoops.roster.min_players' => 3, 'brgyhoops.roster.max_players' => 4]);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager', 'name' => 'Main Manager']);
        $this->rival = User::factory()->create(['role' => 'team_manager', 'name' => 'Rival Manager']);
    }

    private function players(int $count)
    {
        return User::factory()->count($count)->create(['role' => 'player']);
    }

    private function createTournament(string $name = 'Workflow Cup'): Tournament
    {
        $this->actingAs($this->admin)->post(route('admin.tournaments.store'), [
            'name' => $name,
            'type' => 'inter_barangay',
            'status' => 'ongoing',
            'manager_ids' => [$this->manager->id, $this->rival->id],
        ])->assertRedirect(route('admin.tournaments.index'));

        return Tournament::where('name', $name)->firstOrFail();
    }

    private function createTeam(User $manager, Tournament $tournament, string $name): Team
    {
        $this->actingAs($manager)->post(route('tournaments.select', $tournament));
        $this->actingAs($manager)->post(route('teams.store'), [
            'name' => $name,
            'tournament_id' => $tournament->id,
        ])->assertRedirect(route('teams.index'));

        return Team::where('name', $name)->firstOrFail();
    }

    private function requestAndApprove(Team $team, User $manager, $players): void
    {
        foreach ($players as $player) {
            $this->actingAs($player)->post(route('player.teams.request', $team))->assertSessionHas('success');
            $this->actingAs($manager)
                ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $player->id]))
                ->assertSessionHas('success');
        }
    }

    private function approvedTeam(Tournament $tournament, User $manager, string $name): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $tournament->id,
            'user_id' => $manager->id,
            'name' => $name,
            'application_status' => 'approved',
        ])->save();

        return $team;
    }

    /*
    |--------------------------------------------------------------------------
    | The full happy path
    |--------------------------------------------------------------------------
    */

    public function test_complete_workflow_from_tournament_to_scheduled_game(): void
    {
        // 1. Admin creates a tournament.
        $tournament = $this->createTournament();

        // 2. Manager creates a team (starts as draft).
        $team = $this->createTeam($this->manager, $tournament, 'Workflow Wolves');
        $this->assertSame('draft', $team->application_status);

        // 3–4. Players request; manager approves → players are on the roster.
        $players = $this->players(3);
        $this->requestAndApprove($team, $this->manager, $players);

        $this->assertEqualsCanonicalizing($players->pluck('id')->all(), $team->roster()->pluck('users.id')->all());

        // Roster is visible to the manager on the dashboard and teams page.
        foreach (['/manager', '/teams'] as $uri) {
            $response = $this->actingAs($this->manager)->get($uri)->assertOk();

            foreach ($players as $player) {
                $response->assertSee($player->name);
            }
        }

        $this->actingAs($this->manager)->get('/teams')
            ->assertSee('3 / 4 players')
            ->assertSee('Roster complete — ready to submit for admin approval.');

        // 5. Manager submits for approval.
        $this->actingAs($this->manager)
            ->patch(route('teams.submit', $team))
            ->assertRedirect(route('teams.index'))
            ->assertSessionHas('success', 'Team submitted for admin approval.');

        $team->refresh();
        $this->assertSame('pending', $team->application_status);
        $this->assertNotNull($team->applied_at);
        Notification::assertSentTo($this->admin, TeamApplicationSubmitted::class);

        // 6. Admin sees the roster and approves.
        $review = $this->actingAs($this->admin)->get(route('admin.team-applications.index'))->assertOk();
        foreach ($players as $player) {
            $review->assertSee($player->name);
        }

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.approve', $team))
            ->assertSessionHas('success');

        $this->assertSame('approved', $team->fresh()->application_status);
        Notification::assertSentTo($this->manager, TeamApplicationReviewed::class);

        // 7. Approved teams can be scheduled.
        $opponent = $this->approvedTeam($tournament, $this->rival, 'Approved Opponents');

        $this->actingAs($this->admin)->post(route('tournaments.select', $tournament));

        $this->actingAs($this->admin)->get(route('games.create'))
            ->assertOk()
            ->assertSee('Workflow Wolves')
            ->assertSee('Approved Opponents');

        $this->actingAs($this->admin)->post(route('games.store'), [
            'tournament_id' => $tournament->id,
            'home_team_id' => $team->id,
            'away_team_id' => $opponent->id,
            'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ])->assertRedirect(route('games.index'))->assertSessionHasNoErrors();

        $this->assertSame(1, $tournament->games()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Join requests and roster membership
    |--------------------------------------------------------------------------
    */

    public function test_rejecting_a_request_does_not_change_the_roster(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Rejecting Rams');

        $member = $this->players(1)->first();
        $this->requestAndApprove($team, $this->manager, [$member]);

        $applicant = $this->players(1)->first();
        $this->actingAs($applicant)->post(route('player.teams.request', $team));

        $this->actingAs($this->manager)
            ->patch(route('manager.players.reject', ['team' => $team->id, 'playerId' => $applicant->id]))
            ->assertSessionHas('success');

        $this->assertSame([$member->id], $team->roster()->pluck('users.id')->all());
        $this->assertSame('rejected', $team->players()->whereKey($applicant->id)->first()->pivot->status);
    }

    public function test_roster_cannot_exceed_the_maximum(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Full Falcons');

        $this->requestAndApprove($team, $this->manager, $this->players(4));

        // A new request to a full team is refused.
        $late = $this->players(1)->first();
        $this->actingAs($late)->post(route('player.teams.request', $team))
            ->assertSessionHas('error', 'This team\'s roster is full (4 players).');

        // A request that was pending before the roster filled cannot be approved.
        $earlier = $this->players(1)->first();
        $team->players()->attach($earlier->id, ['status' => 'pending']);

        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $earlier->id]))
            ->assertSessionHas('error', 'This team\'s roster is full (4 players).');

        $this->assertSame(4, RosterRules::rosterCount($team));
        $this->assertSame('pending', $team->players()->whereKey($earlier->id)->first()->pivot->status);
    }

    public function test_player_cannot_be_on_two_teams_in_the_same_tournament(): void
    {
        $tournament = $this->createTournament();
        $teamA = $this->createTeam($this->manager, $tournament, 'Alpha Aces');
        $teamB = $this->createTeam($this->rival, $tournament, 'Bravo Bears');

        $player = $this->players(1)->first();

        // Pending requests to both teams are allowed…
        $this->actingAs($player)->post(route('player.teams.request', $teamA))->assertSessionHas('success');
        $this->actingAs($player)->post(route('player.teams.request', $teamB))->assertSessionHas('success');

        // …but once on one roster, the other team cannot approve them.
        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $teamA->id, 'playerId' => $player->id]))
            ->assertSessionHas('success');

        $this->actingAs($this->rival)
            ->patch(route('manager.players.approve', ['team' => $teamB->id, 'playerId' => $player->id]))
            ->assertSessionHas('error', 'This player is already on another team in this tournament.');

        $this->assertSame(0, $teamB->roster()->count());

        // A team in a different tournament is fine.
        $otherTournament = $this->createTournament('Other Cup');
        $teamC = $this->createTeam($this->rival, $otherTournament, 'Charlie Cats');

        $this->requestAndApprove($teamC, $this->rival, [$player]);
        $this->assertSame(1, $teamC->roster()->count());
    }

    public function test_duplicate_and_unauthorized_approvals_are_refused(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Guarded Giants');
        $player = $this->players(1)->first();

        $this->requestAndApprove($team, $this->manager, [$player]);

        // Already a member: no duplicate request or second approval.
        $this->actingAs($player)->post(route('player.teams.request', $team))
            ->assertSessionHas('error', 'You already have a request or membership with this team.');

        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $player->id]))
            ->assertSessionHas('error', 'Pending player request not found.');

        // Another manager cannot approve for this team.
        $applicant = $this->players(1)->first();
        $this->actingAs($applicant)->post(route('player.teams.request', $team));

        $this->actingAs($this->rival)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $applicant->id]))
            ->assertForbidden();

        $this->assertSame(1, $team->players()->where('users.id', $player->id)->count());
        $this->assertSame(1, $team->roster()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Submission eligibility and roster locking
    |--------------------------------------------------------------------------
    */

    public function test_team_below_minimum_cannot_be_submitted(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Short Sharks');
        $this->requestAndApprove($team, $this->manager, $this->players(2));

        $this->actingAs($this->manager)->get('/teams')
            ->assertSee('A team needs at least 3 approved players (currently 2).');

        $this->actingAs($this->manager)
            ->patch(route('teams.submit', $team))
            ->assertRedirect(route('teams.index'))
            ->assertSessionHas('error', 'A team needs at least 3 approved players (currently 2).');

        $this->assertSame('draft', $team->fresh()->application_status);
        Notification::assertNotSentTo($this->admin, TeamApplicationSubmitted::class);
    }

    public function test_only_the_owning_manager_in_the_selected_tournament_can_submit(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Owned Owls');
        $this->requestAndApprove($team, $this->manager, $this->players(3));

        // Rival manager (same tournament selected) cannot submit someone else's team.
        $this->actingAs($this->rival)->post(route('tournaments.select', $tournament));
        $this->actingAs($this->rival)->patch(route('teams.submit', $team))->assertForbidden();

        // Owner with a different tournament selected cannot submit it either.
        $other = $this->createTournament('Other Cup');
        $this->actingAs($this->manager)->post(route('tournaments.select', $other));
        $this->actingAs($this->manager)->patch(route('teams.submit', $team))->assertForbidden();

        // Players and admins cannot use the manager route.
        $this->actingAs($this->players(1)->first())->patch(route('teams.submit', $team))->assertForbidden();
        $this->actingAs($this->admin)->patch(route('teams.submit', $team))->assertForbidden();

        $this->assertSame('draft', $team->fresh()->application_status);
    }

    public function test_roster_is_locked_while_pending_and_after_approval(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Locked Lions');
        $this->requestAndApprove($team, $this->manager, $this->players(3));

        $waiting = $this->players(1)->first();
        $team->players()->attach($waiting->id, ['status' => 'pending']);

        $this->actingAs($this->manager)->patch(route('teams.submit', $team))->assertSessionHas('success');

        // Under review: no new requests or approvals; no double submission.
        $this->actingAs($this->players(1)->first())->post(route('player.teams.request', $team))
            ->assertSessionHas('error', 'This team\'s roster is under admin review and cannot change right now.');

        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $waiting->id]))
            ->assertSessionHas('error', 'This team\'s roster is under admin review and cannot change right now.');

        $this->actingAs($this->manager)->patch(route('teams.submit', $team))
            ->assertSessionHas('error', 'This team has already been submitted and is awaiting admin review.');

        // After approval the roster is closed.
        $this->actingAs($this->admin)->patch(route('admin.team-applications.approve', $team));

        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $waiting->id]))
            ->assertSessionHas('error', 'This team\'s roster has been approved and is closed.');

        $this->assertSame(3, RosterRules::rosterCount($team));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin rejection and resubmission
    |--------------------------------------------------------------------------
    */

    public function test_rejected_team_can_be_corrected_and_resubmitted(): void
    {
        $tournament = $this->createTournament();
        $team = $this->createTeam($this->manager, $tournament, 'Comeback Cougars');
        $this->requestAndApprove($team, $this->manager, $this->players(3));
        $this->actingAs($this->manager)->patch(route('teams.submit', $team));

        // Rejection requires a reason.
        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.reject', $team), ['admin_review_note' => ''])
            ->assertSessionHasErrors('admin_review_note');

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.reject', $team), ['admin_review_note' => 'Please add a fourth player.'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $team->fresh()->application_status);

        // Manager sees the reason and can change the roster again.
        $this->actingAs($this->manager)->post(route('tournaments.select', $tournament));
        $this->actingAs($this->manager)->get('/teams')
            ->assertSee('Please add a fourth player.')
            ->assertSee('Resubmit for Approval');

        $this->requestAndApprove($team, $this->manager, $this->players(1));

        $this->actingAs($this->manager)->patch(route('teams.submit', $team))->assertSessionHas('success');

        $team->refresh();
        $this->assertSame('pending', $team->application_status);
        $this->assertNull($team->admin_review_note);
        $this->assertSame(4, RosterRules::rosterCount($team));
    }

    public function test_admin_cannot_approve_a_pending_team_with_an_invalid_roster(): void
    {
        $tournament = $this->createTournament();

        // A pending team whose roster is below the minimum (e.g. legacy data).
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $tournament->id,
            'user_id' => $this->manager->id,
            'name' => 'Legacy Llamas',
            'application_status' => 'pending',
        ])->save();
        $this->fillRoster($team, 1);

        $this->actingAs($this->admin)->get(route('admin.team-applications.index'))
            ->assertSee('The roster must have between 3 and 4 approved players (currently 1).');

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.approve', $team))
            ->assertSessionHas('error');

        $this->assertSame('pending', $team->fresh()->application_status);
        Notification::assertNotSentTo($this->manager, TeamApplicationReviewed::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Game scheduling eligibility
    |--------------------------------------------------------------------------
    */

    public function test_only_approved_teams_of_the_selected_tournament_can_be_scheduled(): void
    {
        $tournament = $this->createTournament();
        $approvedA = $this->approvedTeam($tournament, $this->manager, 'Approved Ants');
        $approvedB = $this->approvedTeam($tournament, $this->rival, 'Approved Bees');

        $draft = $this->createTeam($this->manager, $tournament, 'Draft Ducks');

        $rejected = $this->approvedTeam($tournament, $this->rival, 'Rejected Rhinos');
        $rejected->forceFill(['application_status' => 'rejected'])->save();

        $pending = $this->approvedTeam($tournament, $this->rival, 'Pending Pandas');
        $pending->forceFill(['application_status' => 'pending'])->save();

        $otherTournament = $this->createTournament('Other Cup');
        $foreignApproved = $this->approvedTeam($otherTournament, $this->rival, 'Foreign Approved');

        $this->actingAs($this->admin)->post(route('tournaments.select', $tournament));

        $this->actingAs($this->admin)->get(route('games.create'))
            ->assertOk()
            ->assertSee('Approved Ants')
            ->assertSee('Approved Bees')
            ->assertDontSee('Draft Ducks')
            ->assertDontSee('Rejected Rhinos')
            ->assertDontSee('Pending Pandas')
            ->assertDontSee('Foreign Approved');

        $payload = fn (Team $home, Team $away) => [
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ];

        foreach ([$draft, $rejected, $pending] as $ineligible) {
            $this->actingAs($this->admin)
                ->post(route('games.store'), $payload($approvedA, $ineligible))
                ->assertRedirect(route('games.create'))
                ->assertSessionHasErrors('away_team_id');
        }

        $this->actingAs($this->admin)
            ->post(route('games.store'), $payload($foreignApproved, $approvedB))
            ->assertSessionHasErrors('home_team_id');

        $this->assertSame(0, Game::count());

        $this->actingAs($this->admin)
            ->post(route('games.store'), $payload($approvedA, $approvedB))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Game::count());

        // A scheduled game cannot be switched to an unapproved team.
        $game = Game::firstOrFail();
        $this->actingAs($this->admin)
            ->put(route('games.update', $game), $payload($approvedA, $draft))
            ->assertSessionHasErrors('away_team_id');

        $this->assertSame($approvedB->id, $game->fresh()->away_team_id);
    }

    public function test_existing_games_with_unapproved_teams_stay_editable(): void
    {
        $tournament = $this->createTournament();
        $legacyHome = $this->createTeam($this->manager, $tournament, 'Legacy Home');
        $legacyAway = $this->createTeam($this->rival, $tournament, 'Legacy Away');

        // A game created before approval was required.
        $game = Game::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $legacyHome->id,
            'away_team_id' => $legacyAway->id,
            'scheduled_at' => '2026-11-01 18:00:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->admin)->post(route('tournaments.select', $tournament));

        $this->actingAs($this->admin)->get(route('games.edit', $game))
            ->assertOk()
            ->assertSee('Legacy Home')
            ->assertSee('Legacy Away');

        $this->actingAs($this->admin)->put(route('games.update', $game), [
            'home_team_id' => $legacyHome->id,
            'away_team_id' => $legacyAway->id,
            'scheduled_at' => '2026-11-01 18:00:00',
            'home_score' => 70,
            'away_score' => 65,
            'status' => 'completed',
        ])->assertRedirect(route('games.index'))->assertSessionHasNoErrors();

        $this->assertSame('completed', $game->fresh()->status);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\PlayerTournaments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlayerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $player;
    private User $manager;
    private Tournament $alpha;   // ongoing
    private Tournament $bravo;   // upcoming
    private Tournament $charlie; // completed, player was on a roster
    private Tournament $delta;   // completed, player never played

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['brgyhoops.roster.min_players' => 2, 'brgyhoops.roster.max_players' => 5]);

        $this->player = User::factory()->create(['role' => 'player', 'name' => 'Paolo Player']);
        $this->manager = User::factory()->create(['role' => 'team_manager', 'name' => 'Mara Manager']);

        $this->alpha = Tournament::create(['name' => 'Alpha League', 'status' => 'ongoing', 'start_date' => '2026-10-01']);
        $this->bravo = Tournament::create(['name' => 'Bravo Cup', 'status' => 'upcoming', 'start_date' => '2026-12-01']);
        $this->charlie = Tournament::create(['name' => 'Charlie Classic', 'status' => 'completed', 'start_date' => '2026-01-01']);
        $this->delta = Tournament::create(['name' => 'Delta Open', 'status' => 'completed', 'start_date' => '2026-02-01']);

        foreach ([$this->alpha, $this->bravo, $this->charlie, $this->delta] as $tournament) {
            $tournament->users()->attach($this->manager->id);
        }
    }

    private function team(Tournament $tournament, string $name, string $status = 'draft'): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $tournament->id,
            'user_id' => $this->manager->id,
            'name' => $name,
            'application_status' => $status,
        ])->save();

        return $team;
    }

    private function asPlayer()
    {
        return $this->actingAs($this->player);
    }

    private function select(Tournament $tournament, string $redirect = 'teams')
    {
        return $this->asPlayer()->post(route('player.tournaments.select'), [
            'tournament_id' => $tournament->id,
            'redirect' => $redirect,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tournament selection
    |--------------------------------------------------------------------------
    */

    public function test_selector_lists_open_tournaments_and_only_own_completed_history(): void
    {
        $this->team($this->charlie, 'Charlie Champs', 'approved')->players()->attach($this->player->id, ['status' => 'approved']);

        $this->assertSame(
            ['Alpha League', 'Bravo Cup', 'Charlie Classic'],
            PlayerTournaments::selectable($this->player)->pluck('name')->all()
        );

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('action="' . route('player.tournaments.select') . '"', false)
            ->assertSee('Alpha League (Ongoing)')
            ->assertSee('Bravo Cup (Upcoming)')
            ->assertSee('Charlie Classic (Completed)')
            ->assertDontSee('Delta Open');

        // A player without history only sees open tournaments.
        $other = User::factory()->create(['role' => 'player']);
        $this->assertSame(['Alpha League', 'Bravo Cup'], PlayerTournaments::selectable($other)->pluck('name')->all());
    }

    public function test_switching_tournaments_scopes_the_team_list(): void
    {
        $this->team($this->alpha, 'Alpha Open Team');
        $this->team($this->bravo, 'Bravo Open Team');

        // Default: the ongoing tournament.
        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('Viewing: <strong>Alpha League</strong>', false)
            ->assertSee('Alpha Open Team')
            ->assertDontSee('Bravo Open Team');

        $this->select($this->bravo)
            ->assertRedirect(route('player.teams.index'))
            ->assertSessionHas('success', 'Now viewing Bravo Cup.')
            ->assertSessionHas(PlayerTournaments::SESSION_KEY, $this->bravo->id);

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertSee('Viewing: <strong>Bravo Cup</strong>', false)
            ->assertSee('Bravo Open Team')
            ->assertDontSee('Alpha Open Team');

        // And back again.
        $this->select($this->alpha);
        $this->asPlayer()->get(route('player.teams.index'))
            ->assertSee('Alpha Open Team')
            ->assertDontSee('Bravo Open Team');
    }

    public function test_cannot_select_unavailable_tournaments(): void
    {
        $this->select($this->delta)->assertForbidden();
        $this->asPlayer()->post(route('player.tournaments.select'), ['tournament_id' => 999999])->assertForbidden();
        $this->asPlayer()->post(route('player.tournaments.select'), [])->assertSessionHasErrors('tournament_id');

        $this->assertNull(session(PlayerTournaments::SESSION_KEY));

        // Player routes stay player-only.
        $this->actingAs($this->manager)->get(route('player.schedule'))->assertForbidden();
        $this->actingAs($this->manager)->post(route('player.tournaments.select'), ['tournament_id' => $this->alpha->id])->assertForbidden();
    }

    public function test_player_selection_does_not_touch_manager_tournament_context(): void
    {
        $this->select($this->bravo);

        $this->assertNull(session('active_tournament_id'));
        $this->assertSame($this->bravo->id, session(PlayerTournaments::SESSION_KEY));
    }

    /*
    |--------------------------------------------------------------------------
    | Team visibility
    |--------------------------------------------------------------------------
    */

    public function test_approved_teams_are_listed_without_exposing_other_rosters(): void
    {
        $approved = $this->team($this->alpha, 'Alpha Approved', 'approved');
        $this->fillRoster($approved, 2)->each(fn ($p) => $p->forceFill(['name' => 'Secret Roster ' . $p->id])->save());

        $draft = $this->team($this->alpha, 'Alpha Draft');
        $this->team($this->bravo, 'Bravo Approved', 'approved');

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('data-participating-team="' . $approved->id . '"', false)
            ->assertDontSee('data-participating-team="' . $draft->id . '"', false)
            ->assertDontSee('Bravo Approved')
            ->assertSee('2 players')
            ->assertDontSee('Secret Roster');
    }

    /*
    |--------------------------------------------------------------------------
    | Membership
    |--------------------------------------------------------------------------
    */

    public function test_approved_membership_shows_my_team_and_roster(): void
    {
        $team = $this->team($this->alpha, 'Alpha Squad');
        $teammate = User::factory()->create(['role' => 'player', 'name' => 'Tina Teammate']);
        $team->players()->attach($teammate->id, ['status' => 'approved']);

        // Real workflow: request → manager approves.
        $this->asPlayer()->post(route('player.teams.request', $team))->assertSessionHas('success');
        $this->actingAs($this->manager)
            ->patch(route('manager.players.approve', ['team' => $team->id, 'playerId' => $this->player->id]))
            ->assertSessionHas('success');

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('data-my-team', false)
            ->assertSee("You're on this roster", false)
            ->assertSee('Alpha Squad')
            ->assertSee('Tina Teammate')
            ->assertSee('Paolo Player')
            ->assertSee('Mara Manager')
            // Already on a team: no join cards in this tournament.
            ->assertDontSee('Teams Looking for Players')
            ->assertDontSee('action="' . route('player.teams.request', $team) . '"', false);
    }

    public function test_pending_request_is_not_membership(): void
    {
        $team = $this->team($this->alpha, 'Pending Panthers');
        $this->asPlayer()->post(route('player.teams.request', $team))->assertSessionHas('success');

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertDontSee('data-my-team', false)
            ->assertSee('No Team Yet')
            ->assertSee('Join Requests')
            ->assertSee('Waiting for the manager to review your request.')
            ->assertSee('Teams Looking for Players');
    }

    public function test_rejected_request_shows_status_and_try_again(): void
    {
        $team = $this->team($this->alpha, 'Rejecting Rams');
        $team->players()->attach($this->player->id, ['status' => 'rejected']);

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertDontSee('data-my-team', false)
            ->assertSee('Not approved. You can request again if the team is still accepting players.')
            ->assertSee('Your previous request was rejected.')
            ->assertSee('Try Again');

        $this->asPlayer()->post(route('player.teams.request', $team))->assertSessionHas('success');
        $this->assertSame('pending', $team->players()->first()->pivot->status);
    }

    public function test_one_team_per_tournament_but_other_tournaments_allowed(): void
    {
        $mine = $this->team($this->alpha, 'Alpha Mine');
        $mine->players()->attach($this->player->id, ['status' => 'approved']);
        $otherAlpha = $this->team($this->alpha, 'Alpha Other');

        $this->asPlayer()->post(route('player.teams.request', $otherAlpha))
            ->assertSessionHas('error', 'This player is already on another team in this tournament.');
        $this->assertSame(0, $otherAlpha->players()->count());

        // A different tournament is open to them.
        $bravoTeam = $this->team($this->bravo, 'Bravo Hopefuls');
        $this->select($this->bravo);

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertSee('No Team Yet')
            ->assertSee('action="' . route('player.teams.request', $bravoTeam) . '"', false);

        $this->asPlayer()->post(route('player.teams.request', $bravoTeam))->assertSessionHas('success');
    }

    public function test_completed_tournament_is_read_only_history(): void
    {
        $team = $this->team($this->charlie, 'Charlie Champs', 'approved');
        $team->players()->attach($this->player->id, ['status' => 'approved']);
        $this->team($this->charlie, 'Charlie Draft');

        $this->select($this->charlie)->assertRedirect(route('player.teams.index'));

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('Charlie Classic is completed — shown read-only for your history.')
            ->assertSee('data-my-team', false)
            ->assertSee('Charlie Champs')
            ->assertDontSee('Teams Looking for Players')
            ->assertDontSee('Request to Join');
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule & standings
    |--------------------------------------------------------------------------
    */

    public function test_schedule_and_standings_follow_the_selected_tournament(): void
    {
        $mine = $this->team($this->alpha, 'Alpha Mine', 'approved');
        $rival = $this->team($this->alpha, 'Alpha Rival', 'approved');
        $mine->players()->attach($this->player->id, ['status' => 'approved']);

        $game = Game::create([
            'tournament_id' => $this->alpha->id, 'home_team_id' => $mine->id, 'away_team_id' => $rival->id,
            'scheduled_at' => '2026-10-20 18:00:00', 'home_score' => 80, 'away_score' => 70,
            'venue' => 'Alpha Arena', 'status' => 'completed',
        ]);

        $bravoHome = $this->team($this->bravo, 'Bravo Home', 'approved');
        $bravoAway = $this->team($this->bravo, 'Bravo Away', 'approved');
        Game::create([
            'tournament_id' => $this->bravo->id, 'home_team_id' => $bravoHome->id, 'away_team_id' => $bravoAway->id,
            'scheduled_at' => '2026-12-05 18:00:00', 'venue' => 'Bravo Gym', 'status' => 'scheduled',
        ]);

        $alphaPage = $this->asPlayer()->get(route('player.schedule'))
            ->assertOk()
            ->assertSee('Alpha Arena')
            ->assertSee('80 - 70')
            ->assertDontSee('Bravo Gym')
            ->assertSee('data-my-game', false)
            ->assertSee('data-my-standing', false)
            // No admin/manager controls for players.
            ->assertDontSee(route('games.edit', $game), false)
            ->assertDontSee(route('games.destroy', $game), false)
            ->assertDontSee(route('games.create'), false)
            ->assertDontSee('data-confirm', false);

        $standings = collect($alphaPage->viewData('standings'))->mapWithKeys(fn ($row) => [$row['team']->name => $row['points']])->all();
        $this->assertSame(['Alpha Mine' => 2, 'Alpha Rival' => 1], $standings);

        // Switching from the schedule page keeps the player on the schedule.
        $this->select($this->bravo, 'schedule')->assertRedirect(route('player.schedule'));

        $bravoPage = $this->asPlayer()->get(route('player.schedule'))
            ->assertOk()
            ->assertSee('Bravo Gym')
            ->assertDontSee('Alpha Arena')
            ->assertDontSee('data-my-game', false);

        $this->assertSame(
            ['Bravo Away' => 0, 'Bravo Home' => 0],
            collect($bravoPage->viewData('standings'))->mapWithKeys(fn ($row) => [$row['team']->name => $row['points']])->all()
        );
    }

    public function test_player_navigation_offers_team_and_schedule(): void
    {
        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('href="' . route('player.teams.index') . '"', false)
            ->assertSee('href="' . route('player.schedule') . '"', false)
            ->assertDontSee('href="' . route('tournaments.index') . '"', false);
    }

    public function test_no_open_tournaments_renders_gracefully(): void
    {
        Tournament::query()->update(['status' => 'completed']);

        $this->asPlayer()->get(route('player.teams.index'))
            ->assertOk()
            ->assertSee('No tournaments are open right now.');

        $this->asPlayer()->get(route('player.schedule'))->assertOk();
    }
}

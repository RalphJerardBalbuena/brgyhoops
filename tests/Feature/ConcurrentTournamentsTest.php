<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two tournaments run at the same time (both "ongoing"). A single manager is
 * assigned to both; data for each must stay isolated and switching between
 * them must change what every page shows and acts on.
 */
class ConcurrentTournamentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $rival;

    private Tournament $alpha;
    private Tournament $bravo;

    /** @var array<string, Team> */
    private array $teams = [];

    /** @var array<string, Game> */
    private array $games = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        $this->manager = User::factory()->create(['role' => 'team_manager', 'name' => 'Main Manager']);
        $this->rival = User::factory()->create(['role' => 'team_manager', 'name' => 'Rival Manager']);

        $this->alpha = Tournament::create(['name' => 'Alpha Cup', 'status' => 'ongoing']);
        $this->bravo = Tournament::create(['name' => 'Bravo League', 'status' => 'ongoing']);

        $this->alpha->users()->attach([$this->manager->id, $this->rival->id]);
        $this->bravo->users()->attach([$this->manager->id, $this->rival->id]);

        $this->teams = [
            'alphaMine' => $this->team($this->alpha, $this->manager, 'Alpha Eagles'),
            'alphaRival' => $this->team($this->alpha, $this->rival, 'Alpha Hawks'),
            'bravoMine' => $this->team($this->bravo, $this->manager, 'Bravo Bulls'),
            'bravoRival' => $this->team($this->bravo, $this->rival, 'Bravo Rams'),
        ];

        // Alpha: manager's team wins. Bravo: manager's team loses.
        $this->games = [
            'alpha' => $this->game($this->alpha, 'alphaMine', 'alphaRival', 90, 70, 'Alpha Court'),
            'bravo' => $this->game($this->bravo, 'bravoMine', 'bravoRival', 60, 75, 'Bravo Gym'),
        ];
    }

    private function team(Tournament $tournament, User $manager, string $name): Team
    {
        $team = Team::create([
            'tournament_id' => $tournament->id,
            'user_id' => $manager->id,
            'name' => $name,
        ]);

        // These fixture teams play scheduled games, which requires admin approval.
        $team->forceFill(['application_status' => 'approved'])->save();

        return $team;
    }

    private function game(Tournament $tournament, string $home, string $away, int $homeScore, int $awayScore, string $venue): Game
    {
        return Game::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $this->teams[$home]->id,
            'away_team_id' => $this->teams[$away]->id,
            'scheduled_at' => now()->subDay(),
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'venue' => $venue,
            'status' => 'completed',
        ]);
    }

    private function gamePayload(Team $home, Team $away, array $overrides = []): array
    {
        return array_merge([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'venue' => 'New Venue',
            'status' => 'scheduled',
        ], $overrides);
    }

    /**
     * @return array<string, int> team name => points
     */
    private function standingsPoints($response): array
    {
        return collect($response->viewData('standings'))
            ->mapWithKeys(fn ($row) => [$row['team']->name => $row['points']])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | (1) Several tournaments can be ongoing at once
    |--------------------------------------------------------------------------
    */

    public function test_multiple_tournaments_can_be_ongoing_simultaneously(): void
    {
        $this->assertSame(2, Tournament::where('status', 'ongoing')->count());

        // An admin can add a third ongoing tournament while two are running.
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), [
                'name' => 'Charlie Classic',
                'type' => 'inter_town',
                // Dates spanning today make the tournament ongoing.
                'start_date' => Tournament::today()->subWeek()->toDateString(),
                'end_date' => Tournament::today()->addMonth()->toDateString(),
                'manager_ids' => [$this->manager->id],
            ])
            ->assertRedirect(route('admin.tournaments.index'));

        $this->assertSame(3, Tournament::where('status', 'ongoing')->count());

        // Existing tournaments are untouched by the new one.
        $this->assertSame('ongoing', $this->alpha->fresh()->status);
        $this->assertSame('ongoing', $this->bravo->fresh()->status);

        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('Alpha Cup')
            ->assertSee('Bravo League')
            ->assertSee('Charlie Classic');

        $this->actingAs($this->admin)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('Alpha Cup')
            ->assertSee('Bravo League')
            ->assertSee('Charlie Classic');
    }

    /*
    |--------------------------------------------------------------------------
    | (2)(3)(5) Manager selects, switches, and sees only that tournament's data
    |--------------------------------------------------------------------------
    */

    public function test_manager_switching_tournaments_updates_every_page(): void
    {
        $this->actingAs($this->manager);

        // Select Alpha.
        $this->post(route('tournaments.select', $this->alpha))
            ->assertRedirect('/manager')
            ->assertSessionHas('active_tournament_id', $this->alpha->id);

        $this->get('/manager')->assertOk()->assertSee('Alpha Cup')->assertDontSee('Bravo League');

        $this->get('/teams')->assertOk()
            ->assertSee('Alpha Eagles')
            ->assertDontSee('Alpha Hawks')   // rival's team, same tournament
            ->assertDontSee('Bravo Bulls');  // own team, other tournament

        $this->get('/games')->assertOk()
            ->assertSee('Alpha Court')
            ->assertDontSee('Bravo Gym');

        $alphaStandings = $this->get('/standings')->assertOk();
        $this->assertSame(['Alpha Eagles' => 2, 'Alpha Hawks' => 1], $this->standingsPoints($alphaStandings));

        // Switch to Bravo.
        $this->post(route('tournaments.select', $this->bravo))
            ->assertRedirect('/manager')
            ->assertSessionHas('active_tournament_id', $this->bravo->id);

        $this->get('/manager')->assertOk()->assertSee('Bravo League')->assertDontSee('Alpha Cup');

        $this->get('/teams')->assertOk()
            ->assertSee('Bravo Bulls')
            ->assertDontSee('Bravo Rams')
            ->assertDontSee('Alpha Eagles');

        $this->get('/games')->assertOk()
            ->assertSee('Bravo Gym')
            ->assertDontSee('Alpha Court');

        $bravoStandings = $this->get('/standings')->assertOk();
        $this->assertSame(['Bravo Rams' => 2, 'Bravo Bulls' => 1], $this->standingsPoints($bravoStandings));
    }

    public function test_manager_actions_follow_the_selected_tournament(): void
    {
        $this->actingAs($this->manager);

        // Creating a team after switching lands in the newly selected tournament.
        $this->post(route('tournaments.select', $this->alpha));
        $this->post(route('teams.store'), ['name' => 'Alpha Newcomers', 'tournament_id' => $this->alpha->id])
            ->assertRedirect(route('teams.index'));

        $this->post(route('tournaments.select', $this->bravo));
        $this->post(route('teams.store'), ['name' => 'Bravo Newcomers', 'tournament_id' => $this->bravo->id])
            ->assertRedirect(route('teams.index'));

        $this->assertSame($this->alpha->id, Team::where('name', 'Alpha Newcomers')->value('tournament_id'));
        $this->assertSame($this->bravo->id, Team::where('name', 'Bravo Newcomers')->value('tournament_id'));

        // With Bravo selected, Alpha teams cannot be edited or deleted.
        $this->get(route('teams.edit', $this->teams['alphaMine']))->assertForbidden();
        $this->put(route('teams.update', $this->teams['alphaMine']), ['name' => 'Hijacked'])->assertForbidden();
        $this->delete(route('teams.destroy', $this->teams['alphaMine']))->assertForbidden();
        $this->assertSame('Alpha Eagles', $this->teams['alphaMine']->fresh()->name);

        // Bravo teams can be edited while Bravo is selected.
        $this->put(route('teams.update', $this->teams['bravoMine']), ['name' => 'Bravo Bulls Renamed'])
            ->assertRedirect(route('teams.index'));
        $this->assertSame('Bravo Bulls Renamed', $this->teams['bravoMine']->fresh()->name);
    }

    public function test_stale_form_from_previous_tournament_is_rejected_after_switching(): void
    {
        $this->actingAs($this->manager);

        $this->post(route('tournaments.select', $this->alpha));
        $this->get(route('teams.create'))->assertOk()
            ->assertSee('name="tournament_id" value="' . $this->alpha->id . '"', false);

        // User switches to Bravo in another tab, then submits the old Alpha form.
        $this->post(route('tournaments.select', $this->bravo));

        $this->post(route('teams.store'), ['name' => 'Stale Team', 'tournament_id' => $this->alpha->id])
            ->assertRedirect(route('teams.create'))
            ->assertSessionHasErrors('tournament_id');

        $this->assertDatabaseMissing('teams', ['name' => 'Stale Team']);
    }

    public function test_manager_cannot_select_unassigned_tournament_or_write_games(): void
    {
        $unassigned = Tournament::create(['name' => 'Delta Open', 'status' => 'ongoing']);

        $this->actingAs($this->manager)
            ->get(route('tournaments.index'))
            ->assertDontSee('Delta Open');

        $this->actingAs($this->manager)
            ->post(route('tournaments.select', $unassigned))
            ->assertForbidden();

        $this->actingAs($this->manager)->post(route('tournaments.select', $this->alpha));

        $this->post(route('games.store'), $this->gamePayload($this->teams['alphaMine'], $this->teams['alphaRival']))
            ->assertForbidden();

        $this->put(route('games.update', $this->games['alpha']), $this->gamePayload(
            $this->teams['alphaMine'], $this->teams['alphaRival'], ['home_score' => 0, 'away_score' => 99, 'status' => 'completed']
        ))->assertForbidden();

        $this->assertSame(90, (int) $this->games['alpha']->fresh()->home_score);
        $this->assertSame(2, Game::count());
    }

    /*
    |--------------------------------------------------------------------------
    | (4) Games only use teams from their own tournament (admin flow)
    |--------------------------------------------------------------------------
    */

    public function test_admin_switches_tournaments_and_games_land_in_the_selected_one(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('tournaments.select', $this->alpha))
            ->assertRedirect(route('games.index'));

        $this->get(route('games.create'))->assertOk()
            ->assertSee('Alpha Eagles')
            ->assertDontSee('Bravo Bulls');

        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['alphaMine'], $this->teams['alphaRival'], ['venue' => 'Alpha Annex', 'tournament_id' => $this->alpha->id]
        ))->assertRedirect(route('games.index'));

        $this->post(route('tournaments.select', $this->bravo))
            ->assertRedirect(route('games.index'));

        $this->get(route('games.create'))->assertOk()
            ->assertSee('Bravo Bulls')
            ->assertDontSee('Alpha Eagles');

        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['bravoMine'], $this->teams['bravoRival'], ['venue' => 'Bravo Annex', 'tournament_id' => $this->bravo->id]
        ))->assertRedirect(route('games.index'));

        $this->assertSame($this->alpha->id, Game::where('venue', 'Alpha Annex')->value('tournament_id'));
        $this->assertSame($this->bravo->id, Game::where('venue', 'Bravo Annex')->value('tournament_id'));

        $this->get('/games')->assertOk()
            ->assertSee('Bravo Annex')
            ->assertDontSee('Alpha Annex');

        // A stale Alpha "schedule game" form is refused while Bravo is selected.
        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['alphaMine'], $this->teams['alphaRival'], ['venue' => 'Stale Venue', 'tournament_id' => $this->alpha->id]
        ))
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors('tournament_id');

        $this->assertDatabaseMissing('games', ['venue' => 'Stale Venue']);
    }

    public function test_admin_cannot_create_or_update_a_game_with_teams_from_another_tournament(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('tournaments.select', $this->alpha));

        $alpha = ['tournament_id' => $this->alpha->id];

        // Mixed tournaments on create: both orderings are rejected.
        $this->post(route('games.store'), $this->gamePayload($this->teams['alphaMine'], $this->teams['bravoRival'], $alpha))
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors('away_team_id')
            ->assertSessionDoesntHaveErrors('home_team_id');

        $this->post(route('games.store'), $this->gamePayload($this->teams['bravoMine'], $this->teams['alphaRival'], $alpha))
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors('home_team_id')
            ->assertSessionDoesntHaveErrors('away_team_id');

        // Both teams from the other tournament are rejected too.
        $this->post(route('games.store'), $this->gamePayload($this->teams['bravoMine'], $this->teams['bravoRival'], $alpha))
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors(['home_team_id', 'away_team_id']);

        $this->assertSame(1, $this->alpha->games()->count());
        $this->assertSame(1, $this->bravo->games()->count());

        // Mixed tournaments on update are rejected and the game is unchanged.
        $this->put(route('games.update', $this->games['alpha']), $this->gamePayload(
            $this->teams['alphaMine'], $this->teams['bravoRival'], ['status' => 'completed', 'home_score' => 90, 'away_score' => 70]
        ))
            ->assertRedirect(route('games.edit', $this->games['alpha']))
            ->assertSessionHasErrors('away_team_id');

        $game = $this->games['alpha']->fresh();
        $this->assertSame($this->teams['alphaRival']->id, $game->away_team_id);
        $this->assertSame('Alpha Court', $game->venue);

        // A game from the other tournament cannot be edited, updated, or deleted.
        $this->get(route('games.edit', $this->games['bravo']))->assertForbidden();
        $this->put(route('games.update', $this->games['bravo']), $this->gamePayload(
            $this->teams['bravoMine'], $this->teams['bravoRival']
        ))->assertForbidden();
        $this->delete(route('games.destroy', $this->games['bravo']))->assertForbidden();

        $this->assertSame('completed', $this->games['bravo']->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Independent standings
    |--------------------------------------------------------------------------
    */

    public function test_standings_are_independent_per_tournament(): void
    {
        $this->actingAs($this->admin);

        // Record a second Alpha result through the admin flow.
        $this->post(route('tournaments.select', $this->alpha));
        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['alphaRival'], $this->teams['alphaMine'],
            ['status' => 'completed', 'home_score' => 50, 'away_score' => 80, 'tournament_id' => $this->alpha->id]
        ))->assertRedirect(route('games.index'));

        $alpha = $this->get('/standings')->assertOk();
        $this->assertSame(['Alpha Eagles' => 4, 'Alpha Hawks' => 2], $this->standingsPoints($alpha));

        // Bravo standings are unaffected by Alpha results.
        $this->post(route('tournaments.select', $this->bravo));
        $bravo = $this->get('/standings')->assertOk();
        $this->assertSame(['Bravo Rams' => 2, 'Bravo Bulls' => 1], $this->standingsPoints($bravo));

        $played = collect($bravo->viewData('standings'))->pluck('played')->all();
        $this->assertSame([1, 1], $played);
    }

    /*
    |--------------------------------------------------------------------------
    | Required tournament_id on create forms
    |--------------------------------------------------------------------------
    */

    public function test_team_create_rejects_missing_invalid_stale_or_unauthorized_tournament_id(): void
    {
        $unassigned = Tournament::create(['name' => 'Delta Open', 'status' => 'ongoing']);

        $this->actingAs($this->manager);
        $this->post(route('tournaments.select', $this->alpha));

        $cases = [
            'missing' => [],
            'non-numeric' => ['tournament_id' => 'abc'],
            'nonexistent' => ['tournament_id' => 999999],
            'stale (assigned, not selected)' => ['tournament_id' => $this->bravo->id],
            'unauthorized' => ['tournament_id' => $unassigned->id],
        ];

        foreach ($cases as $label => $tournamentField) {
            $this->post(route('teams.store'), ['name' => "Rejected {$label}"] + $tournamentField)
                ->assertRedirect(route('teams.create'))
                ->assertSessionHasErrors('tournament_id');

            $this->assertDatabaseMissing('teams', ['name' => "Rejected {$label}"]);
        }

        $this->assertSame(4, Team::count());
        $this->assertSame(0, $unassigned->teams()->count());

        // The matching tournament_id still works.
        $this->post(route('teams.store'), ['name' => 'Accepted Team', 'tournament_id' => $this->alpha->id])
            ->assertRedirect(route('teams.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame($this->alpha->id, Team::where('name', 'Accepted Team')->value('tournament_id'));
    }

    public function test_game_create_rejects_missing_invalid_or_stale_tournament_id(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('tournaments.select', $this->alpha));

        $validTeams = $this->gamePayload($this->teams['alphaMine'], $this->teams['alphaRival']);

        $cases = [
            'missing' => [],
            'non-numeric' => ['tournament_id' => 'abc'],
            'nonexistent' => ['tournament_id' => 999999],
            'stale' => ['tournament_id' => $this->bravo->id],
        ];

        foreach ($cases as $label => $tournamentField) {
            $this->post(route('games.store'), array_merge($validTeams, ['venue' => "Rejected {$label}"], $tournamentField))
                ->assertRedirect(route('games.create'))
                ->assertSessionHasErrors('tournament_id');

            $this->assertDatabaseMissing('games', ['venue' => "Rejected {$label}"]);
        }

        // A stale form with that tournament's teams reports only the stale form.
        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['bravoMine'], $this->teams['bravoRival'], ['tournament_id' => $this->bravo->id]
        ))
            ->assertSessionHasErrors('tournament_id')
            ->assertSessionDoesntHaveErrors(['home_team_id', 'away_team_id']);

        $this->assertSame(2, Game::count());
    }

    public function test_manager_with_valid_tournament_id_still_cannot_create_games(): void
    {
        $this->actingAs($this->manager);
        $this->post(route('tournaments.select', $this->alpha));

        $this->post(route('games.store'), $this->gamePayload(
            $this->teams['alphaMine'], $this->teams['alphaRival'], ['tournament_id' => $this->alpha->id]
        ))->assertForbidden();

        $this->assertSame(2, Game::count());
    }

    /*
    |--------------------------------------------------------------------------
    | Friendly validation errors on the forms
    |--------------------------------------------------------------------------
    */

    public function test_cross_tournament_game_error_is_shown_on_create_form_with_input_kept(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('tournaments.select', $this->alpha));

        $this->followingRedirects()
            ->post(route('games.store'), $this->gamePayload(
                $this->teams['alphaMine'], $this->teams['bravoRival'],
                ['tournament_id' => $this->alpha->id, 'venue' => 'Kept Venue']
            ))
            ->assertOk()
            ->assertSee('Schedule New Game')
            ->assertSee('The away team must belong to Alpha Cup.')
            ->assertSee('value="Kept Venue"', false);

        $this->assertDatabaseMissing('games', ['venue' => 'Kept Venue']);
    }

    public function test_cross_tournament_game_error_is_shown_on_edit_form(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('tournaments.select', $this->alpha));

        $this->followingRedirects()
            ->put(route('games.update', $this->games['alpha']), $this->gamePayload(
                $this->teams['bravoMine'], $this->teams['alphaRival']
            ))
            ->assertOk()
            ->assertSee('Edit Scheduled Game')
            ->assertSee('The home team must belong to Alpha Cup.');

        $this->assertSame($this->teams['alphaMine']->id, $this->games['alpha']->fresh()->home_team_id);
    }

    public function test_stale_team_form_error_is_shown_on_the_new_tournaments_form(): void
    {
        $this->actingAs($this->manager);
        $this->post(route('tournaments.select', $this->alpha));
        $this->post(route('tournaments.select', $this->bravo));

        $this->followingRedirects()
            ->post(route('teams.store'), ['name' => 'Stale Squad', 'tournament_id' => $this->alpha->id])
            ->assertOk()
            ->assertSee('The selected tournament changed. Please review the form and submit again.')
            ->assertSee('Bravo League')
            ->assertSee('name="tournament_id" value="' . $this->bravo->id . '"', false)
            ->assertSee('value="Stale Squad"', false);

        $this->assertDatabaseMissing('teams', ['name' => 'Stale Squad']);
    }

    /*
    |--------------------------------------------------------------------------
    | Tournament switching & navigation
    |--------------------------------------------------------------------------
    */

    public function test_selection_page_lists_only_authorized_tournaments_and_marks_the_current_one(): void
    {
        $unassigned = Tournament::create(['name' => 'Delta Open', 'status' => 'ongoing']);
        $completed = Tournament::create(['name' => 'Echo Finals', 'status' => 'completed']);
        $completed->users()->attach($this->manager->id);

        $this->actingAs($this->manager);

        $page = $this->get(route('tournaments.index'))->assertOk()
            ->assertSee('action="' . route('tournaments.select', $this->alpha) . '"', false)
            ->assertSee('action="' . route('tournaments.select', $this->bravo) . '"', false)
            ->assertDontSee('Delta Open')
            ->assertDontSee('Echo Finals')
            ->assertDontSee(route('tournaments.select', $unassigned), false)
            ->assertDontSee(route('tournaments.select', $completed), false);
        $this->assertSame(0, substr_count($page->getContent(), 'Currently selected'));

        $this->post(route('tournaments.select', $this->alpha));
        $page = $this->get(route('tournaments.index'))->assertOk()
            ->assertSeeInOrder(['Alpha Cup', 'Currently selected', 'Bravo League']);
        $this->assertSame(1, substr_count($page->getContent(), 'Currently selected'));

        $this->post(route('tournaments.select', $this->bravo));
        $page = $this->get(route('tournaments.index'))->assertOk()
            ->assertSeeInOrder(['Alpha Cup', 'Bravo League', 'Currently selected']);
        $this->assertSame(1, substr_count($page->getContent(), 'Currently selected'));
    }

    public function test_manager_assigned_to_one_tournament_cannot_see_or_switch_to_another(): void
    {
        $alphaOnly = User::factory()->create(['role' => 'team_manager']);
        $this->alpha->users()->attach($alphaOnly->id);

        $this->actingAs($alphaOnly)
            ->get(route('tournaments.index'))
            ->assertOk()
            ->assertSee('Alpha Cup')
            ->assertDontSee('Bravo League');

        $this->actingAs($alphaOnly)
            ->post(route('tournaments.select', $this->bravo))
            ->assertForbidden();

        // A forged session for Bravo is refused on every page.
        foreach (['/manager', '/teams', '/games', '/standings'] as $uri) {
            $this->actingAs($alphaOnly)
                ->withSession(['active_tournament_id' => $this->bravo->id])
                ->get($uri)
                ->assertRedirect(route('tournaments.index'));
        }
    }

    public function test_every_tournament_page_links_back_to_the_switcher(): void
    {
        $switcherLink = 'href="' . route('tournaments.index') . '"';

        $this->actingAs($this->manager);
        $this->post(route('tournaments.select', $this->alpha));

        foreach (['/manager', '/teams', '/games', '/standings'] as $uri) {
            $this->get($uri)->assertOk()->assertSee($switcherLink, false);
        }

        $this->actingAs($this->admin);
        $this->post(route('tournaments.select', $this->alpha));

        foreach (['/games', '/standings'] as $uri) {
            $this->get($uri)->assertOk()->assertSee($switcherLink, false);
        }
    }

    public function test_admin_tournament_list_offers_selection_only_for_active_tournaments(): void
    {
        $completed = Tournament::create(['name' => 'Echo Finals', 'status' => 'completed']);

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.index'))
            ->assertOk()
            ->assertSee('action="' . route('tournaments.select', $this->alpha) . '"', false)
            ->assertSee('action="' . route('tournaments.select', $this->bravo) . '"', false)
            ->assertDontSee(route('tournaments.select', $completed), false)
            ->assertSee('href="' . route('public.tournament', $completed) . '"', false);
    }
}

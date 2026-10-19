<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function tournament(string $status = 'ongoing', array $managers = []): Tournament
    {
        $tournament = Tournament::create([
            'name' => 'Tournament ' . uniqid(),
            'status' => $status,
        ]);

        $tournament->users()->attach(collect($managers)->pluck('id'));

        return $tournament;
    }

    private function team(Tournament $tournament, User $manager, string $applicationStatus = 'draft'): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $tournament->id,
            'user_id' => $manager->id,
            'name' => 'Team ' . uniqid(),
            'application_status' => $applicationStatus,
        ])->save();

        return $team;
    }

    private function game(Tournament $tournament, Team $home, Team $away, array $overrides = []): Game
    {
        return Game::create(array_merge([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ], $overrides));
    }

    private function gamePayload(Team $home, Team $away, array $overrides = []): array
    {
        return array_merge([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'home_score' => 80,
            'away_score' => 70,
            'venue' => 'Court A',
            'status' => 'completed',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function test_logout_is_post_only_and_logs_the_user_out(): void
    {
        $manager = $this->user('team_manager');

        $this->actingAs($manager)->get('/logout')->assertStatus(405);

        $this->actingAs($manager)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_dashboards_render_logout_as_csrf_post_form(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('ongoing', [$manager]);

        $logoutAction = 'action="' . route('logout') . '" method="POST"';

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee($logoutAction, false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('href="/logout"', false);

        $this->actingAs($manager)
            ->withSession(['active_tournament_id' => $tournament->id])
            ->get('/manager')
            ->assertOk()
            ->assertSee($logoutAction, false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('href="/logout"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Role authorization
    |--------------------------------------------------------------------------
    */

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/admin', '/manager', '/tournaments', '/teams', '/games', '/standings', '/player/teams'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_players_cannot_access_manager_or_admin_pages(): void
    {
        $player = $this->user('player');
        $tournament = $this->tournament('ongoing', [$player]);

        $uris = [
            '/admin',
            '/admin/tournaments',
            '/admin/tournaments/create',
            '/admin/team-applications',
            '/manager',
            '/manager/player-requests',
            '/tournaments',
            '/teams',
            '/games',
            '/games/create',
            '/standings',
        ];

        foreach ($uris as $uri) {
            $this->actingAs($player)
                ->withSession(['active_tournament_id' => $tournament->id])
                ->get($uri)
                ->assertForbidden();
        }

        $this->actingAs($player)
            ->post("/tournaments/{$tournament->id}/select")
            ->assertForbidden();
    }

    public function test_managers_cannot_access_admin_pages(): void
    {
        $manager = $this->user('team_manager');
        $team = $this->team($this->tournament('ongoing', [$manager]), $manager, 'pending');

        foreach (['/admin', '/admin/tournaments', '/admin/tournaments/create', '/admin/team-applications'] as $uri) {
            $this->actingAs($manager)->get($uri)->assertForbidden();
        }

        $this->actingAs($manager)
            ->patch(route('admin.team-applications.approve', $team))
            ->assertForbidden();

        $this->assertSame('pending', $team->fresh()->application_status);

        $this->actingAs($manager)
            ->post(route('admin.tournaments.store'), [
                'name' => 'Sneaky Cup',
                'type' => 'inter_zone',
                'status' => 'upcoming',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tournaments', ['name' => 'Sneaky Cup']);
    }

    public function test_non_players_cannot_access_player_routes(): void
    {
        $manager = $this->user('team_manager');
        $team = $this->team($this->tournament('ongoing', [$manager]), $manager);

        $this->actingAs($manager)->get('/player/teams')->assertForbidden();
        $this->actingAs($manager)->post(route('player.teams.request', $team))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Newly registered admin and manager routes
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_list_create_and_store_tournaments(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('team_manager');

        $this->actingAs($admin)->get(route('admin.tournaments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.tournaments.create'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.tournaments.store'), [
                'name' => 'Barangay Cup',
                'type' => 'inter_zone',
                'start_date' => Tournament::today()->addWeek()->toDateString(),
                'end_date' => Tournament::today()->addMonth()->toDateString(),
                'manager_ids' => [$manager->id],
            ])
            ->assertRedirect(route('admin.tournaments.index'));

        $tournament = Tournament::where('name', 'Barangay Cup')->firstOrFail();

        $this->assertTrue($tournament->users()->whereKey($manager->id)->exists());
    }

    public function test_admin_can_review_team_applications(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('ongoing', [$manager]);
        $toApprove = $this->team($tournament, $manager, 'pending');
        $toReject = $this->team($tournament, $manager, 'pending');
        $this->fillRoster($toApprove);

        $this->actingAs($admin)->get(route('admin.team-applications.index'))->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.team-applications.approve', $toApprove))
            ->assertRedirect(route('admin.team-applications.index'));

        $this->actingAs($admin)
            ->patch(route('admin.team-applications.reject', $toReject), [
                'admin_review_note' => 'Incomplete roster.',
            ])
            ->assertRedirect(route('admin.team-applications.index'));

        $this->assertSame('approved', $toApprove->fresh()->application_status);
        $this->assertSame('rejected', $toReject->fresh()->application_status);
    }

    public function test_manager_can_review_requests_only_for_own_teams(): void
    {
        $manager = $this->user('team_manager');
        $otherManager = $this->user('team_manager');
        $player = $this->user('player');
        $tournament = $this->tournament('ongoing', [$manager, $otherManager]);

        $ownTeam = $this->team($tournament, $manager);
        $otherTeam = $this->team($tournament, $otherManager);

        $ownTeam->players()->attach($player->id, ['status' => 'pending']);
        $otherTeam->players()->attach($player->id, ['status' => 'pending']);

        $this->actingAs($manager)
            ->get(route('manager.players.requests'))
            ->assertOk()
            ->assertSee($player->name);

        $this->actingAs($manager)
            ->patch(route('manager.players.approve', ['team' => $ownTeam->id, 'playerId' => $player->id]))
            ->assertRedirect();

        $this->actingAs($manager)
            ->patch(route('manager.players.reject', ['team' => $otherTeam->id, 'playerId' => $player->id]))
            ->assertForbidden();

        $this->assertSame('approved', $ownTeam->players()->first()->pivot->status);
        $this->assertSame('pending', $otherTeam->players()->first()->pivot->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Tournament selection & assignment
    |--------------------------------------------------------------------------
    */

    public function test_manager_only_sees_and_selects_assigned_upcoming_or_ongoing_tournaments(): void
    {
        $manager = $this->user('team_manager');
        $assigned = $this->tournament('ongoing', [$manager]);
        $completed = $this->tournament('completed', [$manager]);
        $unassigned = $this->tournament('ongoing');

        $this->actingAs($manager)->get('/tournaments')
            ->assertOk()
            ->assertSee($assigned->name)
            ->assertDontSee($completed->name)
            ->assertDontSee($unassigned->name);

        $this->actingAs($manager)
            ->post(route('tournaments.select', $unassigned))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tournaments.select', $completed))
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('tournaments.select', $assigned))
            ->assertRedirect('/manager')
            ->assertSessionHas('active_tournament_id', $assigned->id);
    }

    public function test_forged_or_completed_session_tournament_is_rejected(): void
    {
        $manager = $this->user('team_manager');
        $unassigned = $this->tournament('ongoing');
        $completed = $this->tournament('completed', [$manager]);

        foreach ([$unassigned, $completed] as $tournament) {
            foreach (['/manager', '/teams', '/games', '/standings'] as $uri) {
                $this->actingAs($manager)
                    ->withSession(['active_tournament_id' => $tournament->id])
                    ->get($uri)
                    ->assertRedirect(route('tournaments.index'))
                    ->assertSessionMissing('active_tournament_id');
            }
        }
    }

    public function test_admin_can_select_any_active_tournament_but_not_completed(): void
    {
        $admin = $this->user('admin');
        $active = $this->tournament('upcoming');
        $completed = $this->tournament('completed');

        $this->actingAs($admin)
            ->post(route('tournaments.select', $active))
            ->assertRedirect(route('games.index'))
            ->assertSessionHas('active_tournament_id', $active->id);

        $this->actingAs($admin)
            ->post(route('tournaments.select', $completed))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Games: admin-only writes, tournament-scoped access
    |--------------------------------------------------------------------------
    */

    public function test_manager_can_view_schedule_of_selected_tournament_only_and_without_edit_controls(): void
    {
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('ongoing', [$manager]);
        $other = $this->tournament('ongoing', [$manager]);

        $game = $this->game($tournament, $this->team($tournament, $manager), $this->team($tournament, $manager));
        $otherGame = $this->game($other, $this->team($other, $manager), $this->team($other, $manager));

        $this->actingAs($manager)
            ->withSession(['active_tournament_id' => $tournament->id])
            ->get('/games')
            ->assertOk()
            ->assertSee($game->homeTeam->name)
            ->assertDontSee($otherGame->homeTeam->name)
            ->assertDontSee(route('games.edit', $game), false)
            ->assertDontSee(route('games.create'), false);
    }

    public function test_manager_cannot_create_edit_score_or_delete_games(): void
    {
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('ongoing', [$manager]);
        $home = $this->team($tournament, $manager);
        $away = $this->team($tournament, $manager);
        $game = $this->game($tournament, $home, $away);

        $session = ['active_tournament_id' => $tournament->id];

        $this->actingAs($manager)->withSession($session)
            ->get(route('games.create'))->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->post(route('games.store'), $this->gamePayload($home, $away))->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->get(route('games.edit', $game))->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->put(route('games.update', $game), $this->gamePayload($home, $away))->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->delete(route('games.destroy', $game))->assertForbidden();

        $this->assertSame(1, Game::count());

        $game->refresh();
        $this->assertNull($game->home_score);
        $this->assertNull($game->away_score);
        $this->assertSame('scheduled', $game->status);
    }

    public function test_admin_can_manage_games_in_selected_tournament(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('ongoing', [$manager]);
        // Only admin-approved teams can be scheduled.
        $home = $this->team($tournament, $manager, 'approved');
        $away = $this->team($tournament, $manager, 'approved');

        $session = ['active_tournament_id' => $tournament->id];

        $this->actingAs($admin)->withSession($session)
            ->get(route('games.create'))->assertOk();

        $this->actingAs($admin)->withSession($session)
            ->post(route('games.store'), $this->gamePayload($home, $away, ['tournament_id' => $tournament->id]))
            ->assertRedirect(route('games.index'));

        $game = Game::firstOrFail();
        $this->assertSame($tournament->id, $game->tournament_id);

        $this->actingAs($admin)->withSession($session)
            ->get('/games')
            ->assertOk()
            ->assertSee(route('games.edit', $game), false);

        $this->actingAs($admin)->withSession($session)
            ->put(route('games.update', $game), $this->gamePayload($home, $away, ['home_score' => 99]))
            ->assertRedirect(route('games.index'));

        $this->assertSame(99, (int) $game->fresh()->home_score);

        $this->actingAs($admin)->withSession($session)
            ->delete(route('games.destroy', $game))
            ->assertRedirect(route('games.index'));

        $this->assertSame(0, Game::count());
    }

    public function test_admin_cannot_touch_games_or_teams_outside_selected_tournament(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('team_manager');
        $selected = $this->tournament('ongoing', [$manager]);
        $other = $this->tournament('ongoing', [$manager]);

        $home = $this->team($selected, $manager);
        $foreignTeam = $this->team($other, $manager);
        $foreignGame = $this->game($other, $foreignTeam, $this->team($other, $manager));

        $session = ['active_tournament_id' => $selected->id];

        $this->actingAs($admin)->withSession($session)
            ->post(route('games.store'), $this->gamePayload($home, $foreignTeam, ['tournament_id' => $selected->id]))
            ->assertRedirect(route('games.create'))
            ->assertSessionHasErrors('away_team_id');

        $this->assertSame(0, $selected->games()->count());

        $this->actingAs($admin)->withSession($session)
            ->get(route('games.edit', $foreignGame))->assertForbidden();

        $this->actingAs($admin)->withSession($session)
            ->put(route('games.update', $foreignGame), $this->gamePayload($foreignTeam, $home))
            ->assertForbidden();

        $this->actingAs($admin)->withSession($session)
            ->delete(route('games.destroy', $foreignGame))->assertForbidden();

        $this->assertNotNull($foreignGame->fresh());
    }

    public function test_admin_without_selected_tournament_is_sent_to_selection(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/games')->assertRedirect(route('tournaments.index'));
        $this->actingAs($admin)->get(route('games.create'))->assertRedirect(route('tournaments.index'));
    }

    /*
    |--------------------------------------------------------------------------
    | Teams & standings scoping
    |--------------------------------------------------------------------------
    */

    public function test_manager_teams_and_standings_are_scoped_to_selected_tournament(): void
    {
        $manager = $this->user('team_manager');
        $selected = $this->tournament('ongoing', [$manager]);
        $other = $this->tournament('ongoing', [$manager]);

        $ownTeam = $this->team($selected, $manager);
        $opponent = $this->team($selected, $this->user('team_manager'));
        $foreignTeam = $this->team($other, $manager);

        $this->game($other, $foreignTeam, $this->team($other, $manager), [
            'status' => 'completed',
            'home_score' => 50,
            'away_score' => 40,
        ]);

        $session = ['active_tournament_id' => $selected->id];

        $this->actingAs($manager)->withSession($session)->get('/teams')
            ->assertOk()
            ->assertSee($ownTeam->name)
            ->assertDontSee($opponent->name)
            ->assertDontSee($foreignTeam->name);

        $this->actingAs($manager)->withSession($session)->get('/standings')
            ->assertOk()
            ->assertSee($ownTeam->name)
            ->assertSee($opponent->name)
            ->assertDontSee($foreignTeam->name);

        $this->actingAs($manager)->withSession($session)
            ->get(route('teams.edit', $foreignTeam))
            ->assertForbidden();

        $this->actingAs($manager)->withSession($session)
            ->delete(route('teams.destroy', $foreignTeam))
            ->assertForbidden();

        $this->assertNotNull($foreignTeam->fresh());
    }

    /*
    |--------------------------------------------------------------------------
    | Public tournament page
    |--------------------------------------------------------------------------
    */

    public function test_public_tournament_page_shows_only_approved_teams(): void
    {
        $manager = $this->user('team_manager');
        $tournament = $this->tournament('completed', [$manager]);

        $approved = $this->team($tournament, $manager, 'approved');
        $pending = $this->team($tournament, $manager, 'pending');
        $draft = $this->team($tournament, $manager, 'draft');
        $rejected = $this->team($tournament, $manager, 'rejected');

        $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertSee($approved->name)
            ->assertDontSee($pending->name)
            ->assertDontSee($draft->name)
            ->assertDontSee($rejected->name);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the shared layout: every page renders with the shared
 * stylesheet and navigation, and each role only sees links it can use.
 */
class UiLayoutTest extends TestCase
{
    use RefreshDatabase;

    private const SHARED_CSS = 'css/brgyhoops-app.css';

    private User $admin;
    private User $manager;
    private User $player;
    private Tournament $tournament;
    private Team $home;
    private Team $away;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);
        $this->player = User::factory()->create(['role' => 'player']);

        $this->tournament = Tournament::create(['name' => 'Layout Cup', 'status' => 'ongoing']);
        $this->tournament->users()->attach($this->manager->id);

        $this->home = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->manager->id, 'name' => 'Layout Home']);
        $this->away = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->manager->id, 'name' => 'Layout Away']);

        $this->game = Game::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);
    }

    private function assertSharedLayout($response): void
    {
        $response->assertOk()
            ->assertSee(self::SHARED_CSS, false)
            ->assertSee('class="navbar"', false)
            ->assertSee('class="footer"', false);
    }

    public function test_guest_pages_use_shared_layout_with_guest_navigation(): void
    {
        foreach (['/forgot-password', '/tournament/' . $this->tournament->id] as $uri) {
            $response = $this->get($uri);

            $this->assertSharedLayout($response);
            $response->assertSee('href="' . route('register') . '"', false)
                ->assertDontSee('action="' . route('logout') . '"', false);
        }
    }

    public function test_homepage_and_auth_pages_still_render(): void
    {
        $this->get('/')->assertOk()->assertSee('One Community.');
        $this->get('/login')->assertOk()->assertSee('Welcome');
        $this->get('/register')->assertOk();

        // The shared alert component renders validation errors on the auth pages.
        $this->from('/login')->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('The email or password is incorrect.')
            ->assertSee('class="alert alert-error"', false);
    }

    public function test_admin_pages_use_shared_layout_and_admin_navigation(): void
    {
        $session = ['active_tournament_id' => $this->tournament->id];

        $uris = [
            route('admin.dashboard'),
            route('admin.tournaments.index'),
            route('admin.tournaments.create'),
            route('admin.team-applications.index'),
            route('tournaments.index'),
            route('games.index'),
            route('games.create'),
            route('games.edit', $this->game),
            route('standings.index'),
        ];

        foreach ($uris as $uri) {
            $response = $this->actingAs($this->admin)->withSession($session)->get($uri);

            $this->assertSharedLayout($response);
            $response->assertSee('href="' . route('admin.team-applications.index') . '"', false)
                ->assertSee('action="' . route('logout') . '" method="POST"', false)
                // Manager-only pages are not linked for admins.
                ->assertDontSee('href="' . route('teams.index') . '"', false)
                ->assertDontSee('href="' . route('manager.dashboard') . '"', false);
        }
    }

    public function test_manager_pages_use_shared_layout_and_manager_navigation(): void
    {
        $session = ['active_tournament_id' => $this->tournament->id];

        $uris = [
            route('manager.dashboard'),
            route('tournaments.index'),
            route('teams.index'),
            route('teams.create'),
            route('teams.edit', $this->home),
            route('games.index'),
            route('standings.index'),
            route('manager.players.requests'),
        ];

        foreach ($uris as $uri) {
            $response = $this->actingAs($this->manager)->withSession($session)->get($uri);

            $this->assertSharedLayout($response);
            $response->assertSee('href="' . route('teams.index') . '"', false)
                ->assertSee('href="' . route('tournaments.index') . '"', false)
                ->assertSee('action="' . route('logout') . '" method="POST"', false)
                // Admin-only pages and game editing are not linked for managers.
                ->assertDontSee('href="' . route('admin.dashboard') . '"', false)
                ->assertDontSee('href="' . route('games.create') . '"', false)
                ->assertDontSee('href="' . route('games.edit', $this->game) . '"', false);
        }
    }

    public function test_player_page_uses_shared_layout_and_player_navigation(): void
    {
        $response = $this->actingAs($this->player)->get(route('player.teams.index'));

        $this->assertSharedLayout($response);
        $response->assertSee('Layout Home')
            ->assertSee('action="' . route('player.teams.request', $this->home) . '"', false)
            ->assertSee('action="' . route('logout') . '" method="POST"', false)
            ->assertDontSee('href="' . route('manager.dashboard') . '"', false)
            ->assertDontSee('href="' . route('admin.dashboard') . '"', false);
    }

    public function test_error_pages_use_shared_layout(): void
    {
        $this->actingAs($this->player)->get('/manager')
            ->assertForbidden()
            ->assertSee(self::SHARED_CSS, false)
            ->assertSee('Access Denied');

        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee(self::SHARED_CSS, false)
            ->assertSee('Page Not Found');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Server-rendered parts of the toast / confirmation / loading UI.
 * (The JavaScript behaviour itself must be verified in a browser.)
 */
class UiFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $player;
    private Tournament $tournament;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Ada Admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager', 'name' => 'Manny Manager']);
        $this->player = User::factory()->create(['role' => 'player', 'name' => 'Pia Player']);

        $this->tournament = Tournament::create(['name' => 'Feedback Cup', 'status' => 'ongoing']);
        $this->tournament->users()->attach($this->manager->id);
    }

    private function team(string $name, string $status = 'draft'): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $this->tournament->id,
            'user_id' => $this->manager->id,
            'name' => $name,
            'application_status' => $status,
        ])->save();

        return $team;
    }

    private function errorBag(array $messages): ViewErrorBag
    {
        return (new ViewErrorBag)->put('default', new MessageBag($messages));
    }

    /*
    |--------------------------------------------------------------------------
    | Toasts
    |--------------------------------------------------------------------------
    */

    public function test_layout_loads_shared_feedback_assets_and_toast_region(): void
    {
        foreach (['/tournament/' . $this->tournament->id, '/forgot-password'] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('css/brgyhoops-ui.css', false)
                ->assertSee('js/brgyhoops-ui.js', false)
                ->assertSee('data-toast-region', false)
                ->assertSee('aria-live="polite"', false);
        }

        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSee('js/brgyhoops-ui.js', false)
            ->assertSee('data-toast-region', false);
    }

    public function test_each_flash_type_renders_as_one_accessible_toast_without_inline_duplicate(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession([
                'success' => 'Saved the tournament.',
                'error' => 'Something needs attention.',
                'warning' => 'Check the roster size.',
                'info' => 'Heads up.',
            ])
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-toast="success" data-toast-message="Saved the tournament."', false)
            ->assertSee('data-toast="error" data-toast-message="Something needs attention."', false)
            ->assertSee('data-toast="warning" data-toast-message="Check the roster size."', false)
            ->assertSee('data-toast="info" data-toast-message="Heads up."', false)
            // Session messages are no longer repeated as inline alerts.
            ->assertDontSee('class="alert alert-success"', false);

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'data-toast-message="Saved the tournament."'));
        $this->assertMatchesRegularExpression('/bh-toast-error"\s+data-toast="error"[^>]*role="alert"/', $html);
        $this->assertMatchesRegularExpression('/bh-toast-success"\s+data-toast="success"[^>]*role="status"/', $html);
    }

    public function test_real_action_flash_reaches_the_next_page_as_a_toast(): void
    {
        $team = $this->team('Toast Titans', 'pending');
        $this->fillRoster($team);

        $this->actingAs($this->admin)
            ->followingRedirects()
            ->patch(route('admin.team-applications.approve', $team))
            ->assertOk()
            ->assertSee('data-toast="success" data-toast-message="Team application approved successfully."', false);
    }

    public function test_validation_errors_show_one_summary_toast_and_keep_inline_messages(): void
    {
        // Single error: the toast shows the message itself.
        $this->actingAs($this->admin)
            ->withSession(['errors' => $this->errorBag(['name' => ['The name field is required.']])])
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertSee('data-toast="error" data-toast-message="The name field is required."', false)
            ->assertSee('Please correct the following:')
            ->assertSee('<div class="field-error">The name field is required.</div>', false);

        // Several errors: one summary toast.
        $this->actingAs($this->admin)
            ->withSession(['errors' => $this->errorBag([
                'name' => ['The name field is required.'],
                'type' => ['The selected type is invalid.'],
                'status' => ['The selected status is invalid.'],
            ])])
            ->get(route('admin.tournaments.create'))
            ->assertSee('data-toast-message="Please fix the 3 highlighted fields and try again."', false)
            ->assertSee('The selected type is invalid.');
    }

    public function test_identical_flash_and_validation_messages_produce_one_toast(): void
    {
        $html = $this->actingAs($this->admin)
            ->withSession([
                'error' => 'The selected tournament changed.',
                'errors' => $this->errorBag(['tournament_id' => ['The selected tournament changed.']]),
            ])
            ->get('/admin')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'data-toast-message="The selected tournament changed."'));
    }

    public function test_toast_messages_are_escaped(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['success' => '<script>alert("x")</script> saved'])
            ->get('/admin')
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; saved', false);
    }

    public function test_error_pages_render_the_toast_region_safely(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('data-toast-region', false)
            ->assertDontSee('data-toast="', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Authentication feedback
    |--------------------------------------------------------------------------
    */

    public function test_successful_login_welcomes_each_role(): void
    {
        $destinations = [
            [$this->admin, route('admin.dashboard')],
            [$this->manager, route('tournaments.index')],
            [$this->player, route('player.teams.index')],
        ];

        foreach ($destinations as [$user, $destination]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect($destination)
                ->assertSessionHas('success', 'Welcome back, ' . $user->name . '!');

            $this->get($destination)
                ->assertOk()
                ->assertSee('data-toast-message="Welcome back, ' . $user->name . '!"', false);

            $this->post('/logout');
        }
    }

    public function test_failed_login_gives_generic_feedback_without_revealing_details(): void
    {
        foreach ([$this->admin->email, 'nobody@example.com'] as $email) {
            $response = $this->from('/login')
                ->post('/login', ['email' => $email, 'password' => 'wrong-password'])
                ->assertRedirect('/login')
                ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

            $this->assertGuest();

            $this->get('/login')
                ->assertOk()
                ->assertSee('data-toast="error" data-toast-message="The email or password is incorrect."', false)
                ->assertSee('class="alert alert-error"', false)
                ->assertDontSee('wrong-password')
                ->assertSee('value="' . $email . '"', false);
        }
    }

    public function test_registration_and_logout_show_success_toasts_on_the_login_page(): void
    {
        $this->post('/register', [
            'name' => 'New Player',
            'email' => 'new.player@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'player',
        ])->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('data-toast="success" data-toast-message="Account created successfully! You can now log in."', false)
            ->assertSee('css/brgyhoops-ui.css', false)
            ->assertSee('js/brgyhoops-ui.js', false);

        $this->actingAs($this->player)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->get('/login')
            ->assertSee('data-toast-message="You have been logged out successfully."', false);
    }

    public function test_register_validation_feedback_is_shown(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Dup',
            'email' => $this->player->email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'player',
        ])->assertRedirect('/register');

        $this->get('/register')
            ->assertOk()
            ->assertSee('data-toast="error"', false)
            ->assertSee('The email has already been taken.');
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmation dialogs & loading markup
    |--------------------------------------------------------------------------
    */

    public function test_destructive_actions_use_the_custom_confirmation_dialog(): void
    {
        $home = $this->team('Delete Home', 'approved');
        $away = $this->team('Delete Away', 'approved');
        $game = Game::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => '2026-11-20 18:00:00',
            'status' => 'scheduled',
        ]);

        $gamesHtml = $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get(route('games.index'))
            ->assertOk()
            ->assertSee('action="' . route('games.destroy', $game) . '"', false)
            ->assertSee('data-confirm="Delete Delete Home vs Delete Away on Nov 20, 2026? This cannot be undone."', false)
            ->assertSee('data-confirm-variant="danger"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-loading-text="Deleting…"', false)
            ->getContent();

        $this->assertStringContainsString('name="_token"', $gamesHtml);

        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get(route('teams.index'))
            ->assertOk()
            ->assertSee('data-confirm="Delete Delete Home? Its roster, join requests, and scheduled games will also be removed. This cannot be undone."', false)
            ->assertSee('data-confirm-action="Delete team"', false)
            ->assertSee('data-confirm-variant="danger"', false);
    }

    public function test_review_actions_use_dialogs_and_no_native_confirm_remains(): void
    {
        $pendingTeam = $this->team('Review Rams', 'pending');
        $this->fillRoster($pendingTeam);

        $draftTeam = $this->team('Draft Ducks');
        $draftTeam->players()->attach($this->player->id, ['status' => 'pending']);
        $this->team('Open Owls');

        $pages = [
            [$this->admin, route('admin.team-applications.index'), ['data-confirm-title="Approve this team?"', 'data-confirm-title="Reject this team?"', 'data-loading-text="Approving…"']],
            [$this->manager, route('manager.players.requests'), ['data-confirm="Add Pia Player to the Draft Ducks roster?"', 'data-confirm-title="Reject this request?"']],
            [$this->player, route('player.teams.index'), ['data-confirm="Send a request to join Open Owls? The team manager will review it."', 'data-loading-text="Sending…"']],
        ];

        foreach ($pages as [$user, $uri, $snippets]) {
            $response = $this->actingAs($user)
                ->withSession(['active_tournament_id' => $this->tournament->id])
                ->get($uri)
                ->assertOk()
                ->assertDontSee('confirm(', false);

            foreach ($snippets as $snippet) {
                $response->assertSee($snippet, false);
            }
        }
    }

    public function test_confirmation_text_is_escaped_in_attributes(): void
    {
        $team = $this->team('Quote "Q" <b>Team</b>');

        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get(route('teams.index'))
            ->assertOk()
            ->assertSee('data-confirm="Delete Quote &quot;Q&quot; &lt;b&gt;Team&lt;/b&gt;?', false)
            ->assertDontSee('data-confirm="Delete Quote "Q"', false);
    }

    public function test_key_submit_buttons_declare_loading_text(): void
    {
        $this->get('/login')->assertSee('data-loading-text="Signing in…"', false);
        $this->get('/register')->assertSee('data-loading-text="Creating account…"', false);

        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get(route('games.create'))
            ->assertSee('data-loading-text="Scheduling…"', false)
            ->assertSee('data-loading-text="Signing out…"', false);

        $this->actingAs($this->manager)
            ->withSession(['active_tournament_id' => $this->tournament->id])
            ->get(route('teams.create'))
            ->assertSee('data-loading-text="Creating team…"', false);
    }
}

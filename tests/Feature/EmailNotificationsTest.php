<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\GameScheduleChanged;
use App\Notifications\GameScheduled;
use App\Notifications\PlayerJoinRequestReceived;
use App\Notifications\PlayerJoinRequestReviewed;
use App\Notifications\PlayerJoinRequestSubmitted;
use App\Notifications\TeamApplicationReviewed;
use App\Support\SafeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $homeManager;
    private User $awayManager;
    private User $bystander;
    private User $player;
    private Tournament $tournament;
    private Team $home;
    private Team $away;

    protected function setUp(): void
    {
        parent::setUp();

        // Never send real email from tests.
        Notification::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->homeManager = User::factory()->create(['role' => 'team_manager', 'name' => 'Home Manager']);
        $this->awayManager = User::factory()->create(['role' => 'team_manager', 'name' => 'Away Manager']);
        $this->bystander = User::factory()->create(['role' => 'team_manager', 'name' => 'Bystander Manager']);
        $this->player = User::factory()->create(['role' => 'player', 'name' => 'Pat Player']);

        $this->tournament = Tournament::create(['name' => 'Mail Cup', 'status' => 'ongoing']);
        $this->tournament->users()->attach([$this->homeManager->id, $this->awayManager->id, $this->bystander->id]);

        $this->home = $this->team($this->homeManager, 'Home Hawks');
        $this->away = $this->team($this->awayManager, 'Away Eagles');
        $this->team($this->bystander, 'Bystander Bulls');
    }

    private function team(User $manager, string $name, string $status = 'approved'): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $this->tournament->id,
            'user_id' => $manager->id,
            'name' => $name,
            'application_status' => $status,
        ])->save();

        return $team;
    }

    private function game(array $overrides = []): Game
    {
        return Game::create(array_merge([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => '2026-11-01 18:00:00',
            'venue' => 'Barangay Covered Court',
            'status' => 'scheduled',
        ], $overrides));
    }

    private function gamePayload(array $overrides = []): array
    {
        return array_merge([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => '2026-11-01 18:00:00',
            'venue' => 'Barangay Covered Court',
            'status' => 'scheduled',
        ], $overrides);
    }

    private function asAdminInTournament()
    {
        return $this->actingAs($this->admin)->withSession(['active_tournament_id' => $this->tournament->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Team application approval / rejection (admin -> team manager)
    |--------------------------------------------------------------------------
    */

    public function test_approving_a_team_application_notifies_only_its_manager(): void
    {
        $team = $this->team($this->homeManager, 'Pending Panthers', 'pending');
        $this->fillRoster($team);

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.approve', $team), ['admin_review_note' => 'Welcome aboard.'])
            ->assertRedirect(route('admin.team-applications.index'));

        Notification::assertSentTo($this->homeManager, TeamApplicationReviewed::class, function ($notification) use ($team) {
            $content = $notification->content($this->homeManager);

            return $notification->team->is($team)
                && $content['heading'] === 'Team application approved'
                && $content['details']['Review note'] === 'Welcome aboard.';
        });

        Notification::assertCount(1);
        Notification::assertNotSentTo([$this->admin, $this->awayManager, $this->bystander, $this->player], TeamApplicationReviewed::class);
    }

    public function test_rejecting_a_team_application_notifies_manager_with_reason(): void
    {
        $team = $this->team($this->awayManager, 'Pending Pumas', 'pending');

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.reject', $team), ['admin_review_note' => 'Roster incomplete.']);

        Notification::assertSentTo($this->awayManager, TeamApplicationReviewed::class, function ($notification) {
            $content = $notification->content($this->awayManager);

            return $content['details']['Decision'] === 'Rejected'
                && $content['details']['Review note'] === 'Roster incomplete.';
        });

        Notification::assertCount(1);
    }

    public function test_reviewing_a_non_pending_application_sends_nothing(): void
    {
        $team = $this->team($this->homeManager, 'Draft Dragons', 'draft');

        $this->actingAs($this->admin)->patch(route('admin.team-applications.approve', $team));
        $this->actingAs($this->homeManager)->patch(route('admin.team-applications.approve', $team))->assertForbidden();

        Notification::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Player join requests (player <-> team manager)
    |--------------------------------------------------------------------------
    */

    public function test_join_request_notifies_team_manager_and_sends_player_receipt(): void
    {
        // Only draft/rejected teams accept join requests.
        $draft = $this->team($this->homeManager, 'Draft Dunkers', 'draft');

        $this->actingAs($this->player)
            ->post(route('player.teams.request', $draft))
            ->assertSessionHas('success');

        Notification::assertSentTo($this->homeManager, PlayerJoinRequestSubmitted::class,
            fn ($notification) => $notification->team->is($draft) && $notification->player->is($this->player));

        Notification::assertSentTo($this->player, PlayerJoinRequestReceived::class);

        Notification::assertCount(2);
        Notification::assertNotSentTo([$this->awayManager, $this->bystander, $this->admin], PlayerJoinRequestSubmitted::class);
    }

    public function test_duplicate_join_request_sends_nothing(): void
    {
        $this->home->players()->attach($this->player->id, ['status' => 'pending']);

        $this->actingAs($this->player)
            ->post(route('player.teams.request', $this->home))
            ->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    public function test_manager_review_of_join_request_notifies_the_player_only(): void
    {
        // Only draft/rejected teams can change their roster.
        $draft = $this->team($this->homeManager, 'Draft Dunkers', 'draft');

        $other = User::factory()->create(['role' => 'player']);
        $draft->players()->attach([
            $this->player->id => ['status' => 'pending'],
            $other->id => ['status' => 'pending'],
        ]);

        $this->actingAs($this->homeManager)
            ->patch(route('manager.players.approve', ['team' => $draft->id, 'playerId' => $this->player->id]));

        $this->actingAs($this->homeManager)
            ->patch(route('manager.players.reject', ['team' => $draft->id, 'playerId' => $other->id]));

        Notification::assertSentTo($this->player, PlayerJoinRequestReviewed::class, fn ($n) => $n->status === 'approved');
        Notification::assertSentTo($other, PlayerJoinRequestReviewed::class, fn ($n) => $n->status === 'rejected');
        Notification::assertCount(2);
    }

    public function test_manager_cannot_trigger_review_emails_for_another_managers_team(): void
    {
        $this->home->players()->attach($this->player->id, ['status' => 'pending']);

        $this->actingAs($this->awayManager)
            ->patch(route('manager.players.approve', ['team' => $this->home->id, 'playerId' => $this->player->id]))
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Game scheduling (admin -> managers of the two teams)
    |--------------------------------------------------------------------------
    */

    public function test_scheduling_a_game_notifies_both_team_managers_only(): void
    {
        $this->asAdminInTournament()
            ->post(route('games.store'), $this->gamePayload())
            ->assertRedirect(route('games.index'));

        $game = Game::firstOrFail();

        Notification::assertSentTo([$this->homeManager, $this->awayManager], GameScheduled::class,
            fn ($notification) => $notification->game->is($game));

        Notification::assertCount(2);
        Notification::assertNotSentTo([$this->bystander, $this->admin, $this->player], GameScheduled::class);
    }

    public function test_manager_owning_both_teams_gets_a_single_email(): void
    {
        $secondHomeTeam = $this->team($this->homeManager, 'Home Hornets');

        $this->asAdminInTournament()
            ->post(route('games.store'), $this->gamePayload(['away_team_id' => $secondHomeTeam->id]));

        Notification::assertSentToTimes($this->homeManager, GameScheduled::class, 1);
        Notification::assertCount(1);
    }

    public function test_recording_a_completed_result_or_invalid_game_sends_nothing(): void
    {
        $this->asAdminInTournament()
            ->post(route('games.store'), $this->gamePayload(['status' => 'completed', 'home_score' => 70, 'away_score' => 60]));

        // Rejected: team from another tournament.
        $otherTournament = Tournament::create(['name' => 'Other Cup', 'status' => 'ongoing']);
        $foreign = Team::create(['tournament_id' => $otherTournament->id, 'user_id' => $this->bystander->id, 'name' => 'Foreign Foxes']);

        $this->asAdminInTournament()
            ->post(route('games.store'), $this->gamePayload(['away_team_id' => $foreign->id]))
            ->assertSessionHasErrors('away_team_id');

        // Rejected: managers cannot schedule games.
        $this->actingAs($this->homeManager)->withSession(['active_tournament_id' => $this->tournament->id])
            ->post(route('games.store'), $this->gamePayload())
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_changing_schedule_notifies_managers_with_what_changed(): void
    {
        $game = $this->game();

        $this->asAdminInTournament()
            ->put(route('games.update', $game), $this->gamePayload([
                'scheduled_at' => '2026-11-02 19:30:00',
                'venue' => 'Municipal Gym',
            ]))
            ->assertRedirect(route('games.index'));

        Notification::assertSentTo([$this->homeManager, $this->awayManager], GameScheduleChanged::class, function ($notification) {
            $changes = implode("\n", $notification->changes);

            return str_contains($changes, 'Date & time: Nov 01, 2026 06:00 PM → Nov 02, 2026 07:30 PM')
                && str_contains($changes, 'Venue: Barangay Covered Court → Municipal Gym');
        });

        Notification::assertCount(2);
        Notification::assertNotSentTo([$this->bystander, $this->player, $this->admin], GameScheduleChanged::class);
    }

    public function test_score_only_or_completion_updates_send_nothing(): void
    {
        $game = $this->game();

        $this->asAdminInTournament()
            ->put(route('games.update', $game), $this->gamePayload([
                'status' => 'completed',
                'home_score' => 81,
                'away_score' => 77,
            ]))
            ->assertRedirect(route('games.index'));

        $this->asAdminInTournament()
            ->put(route('games.update', $game), $this->gamePayload([
                'status' => 'completed',
                'home_score' => 82,
                'away_score' => 77,
            ]));

        Notification::assertNothingSent();
    }

    public function test_swapping_a_team_notifies_old_and_new_managers_once_each(): void
    {
        $game = $this->game();
        $bystanderTeam = Team::where('user_id', $this->bystander->id)->firstOrFail();

        $this->asAdminInTournament()
            ->put(route('games.update', $game), $this->gamePayload(['away_team_id' => $bystanderTeam->id]));

        Notification::assertSentTo($this->awayManager, GameScheduleChanged::class); // removed team
        Notification::assertSentTo($this->bystander, GameScheduleChanged::class, // added team
            fn ($n) => str_contains(implode(' ', $n->changes), 'Matchup: Home Hawks vs Away Eagles → Home Hawks vs Bystander Bulls'));
        Notification::assertSentToTimes($this->homeManager, GameScheduleChanged::class, 1);
        Notification::assertCount(3);
    }

    public function test_cancelling_a_game_sends_a_cancellation_email(): void
    {
        $game = $this->game();

        $this->asAdminInTournament()
            ->put(route('games.update', $game), $this->gamePayload(['status' => 'cancelled']));

        Notification::assertSentTo($this->homeManager, GameScheduleChanged::class, function ($notification) {
            $content = $notification->content($this->homeManager);

            return $content['subject'] === 'Game cancelled: Home Hawks vs Away Eagles'
                && $content['heading'] === 'Game cancelled';
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Safety: missing addresses, mail failures, content
    |--------------------------------------------------------------------------
    */

    public function test_recipients_without_a_valid_email_are_skipped(): void
    {
        $this->awayManager->forceFill(['email' => 'not-an-email'])->save();

        $this->asAdminInTournament()->post(route('games.store'), $this->gamePayload());

        Notification::assertSentTo($this->homeManager, GameScheduled::class);
        Notification::assertNotSentTo($this->awayManager, GameScheduled::class);

        SafeNotifier::send(null, new GameScheduled(Game::firstOrFail()));
        SafeNotifier::send([null], new GameScheduled(Game::firstOrFail()));
        Notification::assertCount(1);
    }

    public function test_mail_failure_is_reported_but_does_not_break_the_action(): void
    {
        Exceptions::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('Resend API key missing'));

        $team = $this->team($this->homeManager, 'Pending Pistons', 'pending');
        $this->fillRoster($team);

        $this->actingAs($this->admin)
            ->patch(route('admin.team-applications.approve', $team))
            ->assertRedirect(route('admin.team-applications.index'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $team->fresh()->application_status);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_email_renders_branded_escaped_content_without_other_users_emails(): void
    {
        $this->home->forceFill(['name' => 'Hawks <script>alert(1)</script>'])->save();
        $game = $this->game(['venue' => 'Court & Gym']);

        $message = (new GameScheduled($game->fresh()))->toMail($this->awayManager);

        $this->assertStringStartsWith('BrgyHoops: Game scheduled:', $message->subject);

        $html = view('emails.notification', $message->viewData)->render();

        $this->assertStringContainsString('Brgy<span style="color:#e21b23;">Hoops</span>', $html);
        $this->assertStringContainsString('Hi Away Manager,', $html);
        $this->assertStringContainsString('Court &amp; Gym', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString(route('public.tournament', $this->tournament->id), $html);

        foreach ([$this->homeManager, $this->bystander, $this->admin, $this->player] as $other) {
            $this->assertStringNotContainsString($other->email, $html);
        }

        $text = view('emails.notification-text', $message->viewData)->render();
        $this->assertStringContainsString('Venue: Court & Gym', $text);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\GameScheduleChanged;
use App\Support\GoogleMaps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameCoordinatesTest extends TestCase
{
    use RefreshDatabase;

    private const LEAFLET_JS = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';

    private User $admin;
    private User $manager;
    private Tournament $tournament;
    private Team $home;
    private Team $away;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);

        $this->tournament = Tournament::create(['name' => 'Pin Cup', 'status' => 'ongoing']);
        $this->tournament->users()->attach($this->manager->id);

        $this->home = $this->approvedTeam('Pin Home');
        $this->away = $this->approvedTeam('Pin Away');
    }

    private function approvedTeam(string $name): Team
    {
        $team = new Team();
        $team->forceFill([
            'tournament_id' => $this->tournament->id,
            'user_id' => $this->manager->id,
            'name' => $name,
            'application_status' => 'approved',
        ])->save();

        return $team;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => '2026-11-10 18:00:00',
            'venue' => 'Manila City Hall Court',
            'status' => 'scheduled',
        ], $overrides);
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession(['active_tournament_id' => $this->tournament->id]);
    }

    private function game(array $overrides = []): Game
    {
        return Game::create(array_merge([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => '2026-11-10 18:00:00',
            'venue' => 'Manila City Hall Court',
            'status' => 'scheduled',
        ], $overrides));
    }

    /*
    |--------------------------------------------------------------------------
    | Creating and updating coordinates
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_a_game_with_exact_coordinates(): void
    {
        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['latitude' => '14.5995', 'longitude' => '120.9842']))
            ->assertRedirect(route('games.index'))
            ->assertSessionHasNoErrors();

        $game = Game::firstOrFail();

        $this->assertEqualsWithDelta(14.5995, $game->latitude, 1e-9);
        $this->assertEqualsWithDelta(120.9842, $game->longitude, 1e-9);
        $this->assertSame('Manila City Hall Court', $game->venue);
    }

    public function test_coordinates_are_stored_with_at_most_seven_decimals(): void
    {
        $this->asAdmin()->post(route('games.store'), $this->payload([
            'latitude' => '14.599512345678',
            'longitude' => '-120.984298765432',
        ]))->assertSessionHasNoErrors();

        $game = Game::firstOrFail();

        $this->assertEqualsWithDelta(14.5995123, $game->latitude, 1e-9);
        $this->assertEqualsWithDelta(-120.9842988, $game->longitude, 1e-9);
    }

    public function test_boundary_coordinates_are_accepted(): void
    {
        foreach ([[90, 180], [-90, -180], [0, 0]] as [$lat, $lng]) {
            $this->asAdmin()
                ->post(route('games.store'), $this->payload(['latitude' => $lat, 'longitude' => $lng]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, Game::whereNotNull('latitude')->count());
    }

    public function test_admin_can_update_move_and_remove_the_pin(): void
    {
        $game = $this->game(['latitude' => 14.5995, 'longitude' => 120.9842]);

        // Move the pin.
        $this->asAdmin()
            ->put(route('games.update', $game), $this->payload(['latitude' => '10.3157', 'longitude' => '123.8854']))
            ->assertRedirect(route('games.index'))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(10.3157, $game->fresh()->latitude, 1e-9);
        $this->assertEqualsWithDelta(123.8854, $game->fresh()->longitude, 1e-9);

        // A request without the coordinate fields keeps the pin.
        $this->asAdmin()->put(route('games.update', $game), $this->payload(['venue' => 'Renamed Court']));
        $this->assertEqualsWithDelta(10.3157, $game->fresh()->latitude, 1e-9);
        $this->assertSame('Renamed Court', $game->fresh()->venue);

        // Submitting both blank (the form's "Remove pin") clears it.
        $this->asAdmin()
            ->put(route('games.update', $game), $this->payload(['latitude' => '', 'longitude' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($game->fresh()->latitude);
        $this->assertNull($game->fresh()->longitude);
        $this->assertSame('Manila City Hall Court', $game->fresh()->venue);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    public function test_out_of_range_or_non_numeric_coordinates_are_rejected(): void
    {
        $cases = [
            'latitude' => [['latitude' => '90.0001', 'longitude' => '120'], ['latitude' => '-91', 'longitude' => '120'], ['latitude' => 'north', 'longitude' => '120']],
            'longitude' => [['latitude' => '14', 'longitude' => '180.5'], ['latitude' => '14', 'longitude' => '-181'], ['latitude' => '14', 'longitude' => 'east']],
        ];

        foreach ($cases as $field => $variants) {
            foreach ($variants as $coordinates) {
                $this->asAdmin()
                    ->post(route('games.store'), $this->payload($coordinates))
                    ->assertRedirect(route('games.create'))
                    ->assertSessionHasErrors($field);
            }
        }

        $this->assertSame(0, Game::count());
    }

    public function test_range_error_messages_are_clear(): void
    {
        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['latitude' => '95', 'longitude' => '200']))
            ->assertSessionHasErrors([
                'latitude' => 'The latitude must be between -90 and 90.',
                'longitude' => 'The longitude must be between -180 and 180.',
            ]);
    }

    public function test_only_one_coordinate_is_rejected(): void
    {
        $message = 'Enter both latitude and longitude for the map pin, or leave both blank.';

        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['latitude' => '14.5995', 'longitude' => '']))
            ->assertSessionHasErrors(['longitude' => $message]);

        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['longitude' => '120.9842']))
            ->assertSessionHasErrors(['latitude' => $message]);

        $this->assertSame(0, Game::count());

        // Same rule on update; the existing pin is untouched.
        $game = $this->game(['latitude' => 14.5995, 'longitude' => 120.9842]);

        $this->asAdmin()
            ->put(route('games.update', $game), $this->payload(['latitude' => '', 'longitude' => '121.5']))
            ->assertRedirect(route('games.edit', $game))
            ->assertSessionHasErrors('latitude');

        $this->assertEqualsWithDelta(120.9842, $game->fresh()->longitude, 1e-9);
    }

    public function test_existing_rules_still_apply_with_coordinates(): void
    {
        // Managers still cannot create games.
        $this->actingAs($this->manager)->withSession(['active_tournament_id' => $this->tournament->id])
            ->post(route('games.store'), $this->payload(['latitude' => '14.5', 'longitude' => '120.9']))
            ->assertForbidden();

        // Unapproved teams are still rejected.
        $draft = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $this->manager->id, 'name' => 'Draft Team']);

        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['away_team_id' => $draft->id, 'latitude' => '14.5', 'longitude' => '120.9']))
            ->assertSessionHasErrors('away_team_id');

        $this->assertSame(0, Game::count());
    }

    public function test_coordinate_only_changes_send_no_schedule_email_but_venue_changes_still_do(): void
    {
        $game = $this->game();

        $this->asAdmin()->put(route('games.update', $game), $this->payload(['latitude' => '14.5995', 'longitude' => '120.9842']));
        Notification::assertNothingSent();

        $this->asAdmin()->put(route('games.update', $game), $this->payload([
            'venue' => 'Quezon City Gym',
            'latitude' => '14.5995',
            'longitude' => '120.9842',
        ]));
        Notification::assertSentTo($this->manager, GameScheduleChanged::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin form
    |--------------------------------------------------------------------------
    */

    public function test_create_form_has_map_picker_and_coordinate_fields(): void
    {
        $this->asAdmin()->get(route('games.create'))
            ->assertOk()
            ->assertSee('data-location-picker', false)
            ->assertSee('name="latitude"', false)
            ->assertSee('name="longitude"', false)
            ->assertSee('src="' . self::LEAFLET_JS . '"', false)
            ->assertSee('integrity="sha512-', false)
            ->assertSee('js/brgyhoops-maps.js', false)
            ->assertSee('OpenStreetMap</a> contributors', false);
    }

    public function test_edit_form_shows_saved_coordinates(): void
    {
        $game = $this->game(['latitude' => 14.5995, 'longitude' => 120.9842]);

        $this->asAdmin()->get(route('games.edit', $game))
            ->assertOk()
            ->assertSee('value="14.5995"', false)
            ->assertSee('value="120.9842"', false)
            ->assertSee('value="Manila City Hall Court"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Public display
    |--------------------------------------------------------------------------
    */

    public function test_public_card_shows_map_and_exact_directions_for_pinned_game(): void
    {
        $this->game(['latitude' => 14.5995, 'longitude' => 120.9842]);

        $html = $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('data-game-map', false)
            ->assertSee('data-lat="14.5995"', false)
            ->assertSee('data-lng="120.9842"', false)
            ->assertSee('href="https://www.google.com/maps/dir/?api=1&amp;destination=14.5995,120.9842"', false)
            ->assertSee('Directions to exact location')
            ->assertSee('OpenStreetMap</a> contributors', false)
            ->assertSee('src="' . self::LEAFLET_JS . '"', false)
            ->getContent();

        // Leaflet is loaded once even with several pinned games.
        $this->assertSame(1, substr_count($html, self::LEAFLET_JS));

        // The pinned venue is not mapped again in the Google "Game Venues" section.
        $this->assertStringNotContainsString('Game Venues', $html);
    }

    public function test_negative_and_zero_coordinates_format_exactly(): void
    {
        $this->game(['latitude' => -33.8688, 'longitude' => 151.2093]);
        $this->game(['latitude' => 0, 'longitude' => 0, 'venue' => 'Null Island']);

        $html = $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('destination=-33.8688,151.2093', false)
            ->assertSee('destination=0,0', false)
            ->getContent();

        // Two pinned games: two maps, but Leaflet and the map script load once.
        $this->assertSame(2, substr_count($html, 'data-game-map'));
        $this->assertSame(1, substr_count($html, self::LEAFLET_JS));
        $this->assertSame(1, substr_count($html, 'js/brgyhoops-maps.js'));

        $this->assertSame('https://www.google.com/maps/dir/?api=1&destination=14.5,-120', GoogleMaps::coordinateDirectionsUrl(14.5, -120.0));
        $this->assertNull(GoogleMaps::coordinateDirectionsUrl(91, 0));
        $this->assertNull(GoogleMaps::coordinateDirectionsUrl(null, 120));
    }

    public function test_game_without_coordinates_keeps_existing_venue_behavior(): void
    {
        config(['services.google_maps.embed_key' => null]);
        $this->game(['venue' => 'Municipal Gym']);

        $html = $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('Municipal Gym')
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=Municipal%20Gym', false)
            ->assertSee('Directions to venue')
            ->assertSee('Game Venues')
            ->assertDontSee('data-game-map', false)
            ->assertDontSee('Directions to exact location')
            ->getContent();

        // No Leaflet is loaded on pages without pinned games.
        $this->assertStringNotContainsString(self::LEAFLET_JS, $html);
    }

    public function test_coordinates_and_venue_text_are_escaped(): void
    {
        $this->game(['venue' => '<script>alert(1)</script> Court', 'latitude' => 14.5, 'longitude' => 120.9]);

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('data-label="&lt;script&gt;alert(1)&lt;/script&gt; Court"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Before the coordinate migration is applied
    |--------------------------------------------------------------------------
    */

    public function test_games_work_before_the_coordinate_columns_exist(): void
    {
        Schema::table('games', fn ($table) => $table->dropColumn(['latitude', 'longitude']));
        $this->assertFalse(Game::supportsCoordinates());

        $this->asAdmin()->get(route('games.create'))
            ->assertOk()
            ->assertSee('name="venue"', false)
            ->assertDontSee('name="latitude"', false)
            ->assertDontSee('data-location-picker', false);

        $this->asAdmin()
            ->post(route('games.store'), $this->payload(['latitude' => '14.5995', 'longitude' => '120.9842']))
            ->assertRedirect(route('games.index'))
            ->assertSessionHasNoErrors();

        $game = Game::firstOrFail();
        $this->assertArrayNotHasKey('latitude', $game->getAttributes());

        $this->asAdmin()->put(route('games.update', $game), $this->payload(['venue' => 'Updated Court']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Updated Court', $game->fresh()->venue);

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('Updated Court')
            ->assertDontSee('data-game-map', false);
    }
}

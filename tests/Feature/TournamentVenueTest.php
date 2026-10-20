<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TournamentVenueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Venue Cup',
            'type' => 'inter_barangay',
            ...$this->formatFields(),
            'start_date' => Tournament::today()->subWeek()->toDateString(),
            'end_date' => Tournament::today()->addMonth()->toDateString(),
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Saving
    |--------------------------------------------------------------------------
    */

    public function test_admin_create_form_has_optional_venue_field(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertSee('name="venue"', false)
            ->assertSee('Primary venue');
    }

    public function test_admin_can_save_a_tournament_venue(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->storePayload([
                'venue' => 'San Isidro Covered Court, Quezon City',
            ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'San Isidro Covered Court, Quezon City',
            Tournament::where('name', 'Venue Cup')->value('venue')
        );

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.index'))
            ->assertSee('San Isidro Covered Court, Quezon City');
    }

    public function test_venue_is_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->storePayload(['venue' => '']))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull(Tournament::where('name', 'Venue Cup')->value('venue'));
    }

    public function test_venue_is_validated(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->storePayload(['venue' => str_repeat('x', 256)]))
            ->assertSessionHasErrors('venue');

        $this->assertDatabaseMissing('tournaments', ['name' => 'Venue Cup']);
    }

    public function test_non_admins_cannot_save_venues(): void
    {
        $manager = User::factory()->create(['role' => 'team_manager']);

        $this->actingAs($manager)
            ->post(route('admin.tournaments.store'), $this->storePayload(['venue' => 'Sneaky Gym']))
            ->assertForbidden();

        $this->assertDatabaseMissing('tournaments', ['venue' => 'Sneaky Gym']);
    }

    public function test_creating_tournaments_still_works_before_the_venue_migration_is_applied(): void
    {
        Schema::table('tournaments', fn ($table) => $table->dropColumn('venue'));

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertDontSee('name="venue"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $this->storePayload(['venue' => 'Ignored Court']))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $tournament = Tournament::where('name', 'Venue Cup')->firstOrFail();

        $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertSee('To be announced')
            ->assertDontSee('Ignored Court');
    }

    /*
    |--------------------------------------------------------------------------
    | Displaying
    |--------------------------------------------------------------------------
    */

    public function test_public_page_shows_venue_map_and_directions_with_api_key(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);
        $tournament = Tournament::create(['name' => 'Map Cup', 'status' => 'ongoing', 'venue' => 'Quezon City Hall Gym & Court']);

        $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertSee('Tournament Venue')
            ->assertSee('Quezon City Hall Gym &amp; Court', false)
            ->assertSee('src="https://www.google.com/maps/embed/v1/place?key=test-embed-key&amp;q=Quezon%20City%20Hall%20Gym%20%26%20Court"', false)
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=Quezon%20City%20Hall%20Gym%20%26%20Court', false);
    }

    public function test_public_page_shows_address_and_directions_without_api_key(): void
    {
        config(['services.google_maps.embed_key' => null]);
        $tournament = Tournament::create(['name' => 'No Key Cup', 'status' => 'ongoing', 'venue' => 'Pasig Sports Center']);

        $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertSee('Pasig Sports Center')
            ->assertSee('Map preview unavailable')
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=Pasig%20Sports%20Center', false)
            ->assertDontSee('<iframe', false);
    }

    public function test_public_page_handles_missing_or_placeholder_venue(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);

        foreach ([null, '', 'TBA'] as $index => $venue) {
            $tournament = Tournament::create(['name' => "Empty Venue Cup {$index}", 'status' => 'ongoing', 'venue' => $venue]);

            $this->get(route('public.tournament', $tournament))
                ->assertOk()
                ->assertSee('Tournament Venue')
                ->assertSee('To be announced')
                ->assertDontSee('<iframe', false)
                ->assertDontSee('maps/dir', false);
        }
    }

    public function test_venue_value_is_escaped(): void
    {
        $tournament = Tournament::create(['name' => 'Escape Cup', 'status' => 'ongoing', 'venue' => '<script>alert(1)</script> Court']);

        $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Independence from games.venue
    |--------------------------------------------------------------------------
    */

    public function test_game_venues_stay_independent_and_are_not_mapped_twice(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);

        $manager = User::factory()->create(['role' => 'team_manager']);
        $tournament = Tournament::create(['name' => 'Two Venue Cup', 'status' => 'ongoing', 'venue' => 'Main Arena']);
        $tournament->users()->attach($manager->id);

        $home = Team::create(['tournament_id' => $tournament->id, 'user_id' => $manager->id, 'name' => 'Venue Home']);
        $away = Team::create(['tournament_id' => $tournament->id, 'user_id' => $manager->id, 'name' => 'Venue Away']);

        foreach (['Main Arena', 'Side Court', null] as $venue) {
            Game::create([
                'tournament_id' => $tournament->id,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'scheduled_at' => now()->addDay(),
                'venue' => $venue,
                'status' => 'scheduled',
            ]);
        }

        $html = $this->get(route('public.tournament', $tournament))
            ->assertOk()
            ->assertSee('Main Arena')
            ->assertSee('Side Court')
            ->getContent();

        // One map for the tournament venue, one for the different game venue.
        $this->assertSame(2, substr_count($html, '<iframe'));

        // Game venues are never overwritten by the tournament venue.
        $this->assertSame(['Main Arena', 'Side Court', null], Game::orderBy('id')->pluck('venue')->all());

        // Admin game edits still save the game's own venue.
        $game = Game::whereNull('venue')->firstOrFail();

        $this->actingAs($this->admin)
            ->withSession(['active_tournament_id' => $tournament->id])
            ->put(route('games.update', $game), [
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'scheduled_at' => $game->scheduled_at->format('Y-m-d H:i:s'),
                'venue' => 'Annex Gym',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('games.index'));

        $this->assertSame('Annex Gym', $game->fresh()->venue);
        $this->assertSame('Main Arena', $tournament->fresh()->venue);
    }
}

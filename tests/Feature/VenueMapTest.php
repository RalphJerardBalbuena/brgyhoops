<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\GoogleMaps;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueMapTest extends TestCase
{
    use RefreshDatabase;

    private Tournament $tournament;
    private Team $home;
    private Team $away;

    protected function setUp(): void
    {
        parent::setUp();

        $manager = User::factory()->create(['role' => 'team_manager']);
        $this->tournament = Tournament::create(['name' => 'Map Cup', 'status' => 'ongoing']);
        $this->home = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $manager->id, 'name' => 'Map Home']);
        $this->away = Team::create(['tournament_id' => $this->tournament->id, 'user_id' => $manager->id, 'name' => 'Map Away']);
    }

    private function scheduleAt(?string $venue): Game
    {
        return Game::create([
            'tournament_id' => $this->tournament->id,
            'home_team_id' => $this->home->id,
            'away_team_id' => $this->away->id,
            'scheduled_at' => now()->addDay(),
            'venue' => $venue,
            'status' => 'scheduled',
        ]);
    }

    public function test_without_api_key_shows_address_and_directions_but_no_iframe(): void
    {
        config(['services.google_maps.embed_key' => null]);
        $this->scheduleAt('San Isidro Covered Court, Quezon City');

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('Game Venues')
            ->assertSee('San Isidro Covered Court, Quezon City')
            ->assertSee('Map preview unavailable')
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=San%20Isidro%20Covered%20Court%2C%20Quezon%20City', false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('maps/embed', false);
    }

    public function test_with_api_key_renders_embed_iframe_with_encoded_venue(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);
        $this->scheduleAt('Brgy. Hall & Gym, San José');

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('src="https://www.google.com/maps/embed/v1/place?key=test-embed-key&amp;q=Brgy.%20Hall%20%26%20Gym%2C%20San%20Jos%C3%A9"', false)
            ->assertSee('loading="lazy"', false)
            ->assertDontSee('Map preview unavailable');
    }

    public function test_placeholder_or_missing_venues_render_no_map(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);
        $this->scheduleAt(null);
        $this->scheduleAt('TBA');
        $this->scheduleAt('   ');

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertDontSee('Game Venues')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('maps/dir', false);
    }

    public function test_duplicate_venues_render_one_map(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);
        $this->scheduleAt('Municipal Gym');
        $this->scheduleAt('municipal gym');

        $html = $this->get(route('public.tournament', $this->tournament))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<iframe'));
    }

    public function test_venue_text_is_escaped(): void
    {
        config(['services.google_maps.embed_key' => 'test-embed-key']);
        $this->scheduleAt('<script>alert("x")</script> Court');

        $this->get(route('public.tournament', $this->tournament))
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_url_helpers_encode_and_reject_unusable_values(): void
    {
        config(['services.google_maps.embed_key' => 'k']);

        $this->assertSame(
            'https://www.google.com/maps/embed/v1/place?key=k&q=A%20%2B%20B%20%26%20C%3F',
            GoogleMaps::embedUrl('  A + B   & C? ')
        );
        $this->assertNull(GoogleMaps::embedUrl('to be announced'));
        $this->assertNull(GoogleMaps::directionsUrl(''));

        config(['services.google_maps.embed_key' => '   ']);
        $this->assertNull(GoogleMaps::embedUrl('Real Venue'));
        $this->assertNotNull(GoogleMaps::directionsUrl('Real Venue'));
    }
}

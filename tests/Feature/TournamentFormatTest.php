<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tournament format and finals format: chosen by admins on create/edit,
 * shown to managers on open-tournament details. Existing tournaments keep
 * no format ("Not specified") until they are edited.
 */
class TournamentFormatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'team_manager']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Format Cup',
            'type' => 'inter_barangay',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
            'tournament_format' => 'single_elimination',
            'finals_format' => 'best_of_3',
        ], $overrides);
    }

    private function store(array $overrides = [])
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.tournaments.create'))
            ->post(route('admin.tournaments.store'), $this->payload($overrides));
    }

    private function update(Tournament $tournament, array $payload)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.tournaments.edit', $tournament))
            ->put(route('admin.tournaments.update', $tournament), $payload);
    }

    /**
     * A tournament created before this story: no formats stored.
     */
    private function legacy(array $attributes = []): Tournament
    {
        return Tournament::create(array_merge([
            'name' => 'Legacy Cup',
            'type' => 'inter_zone',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin forms
    |--------------------------------------------------------------------------
    */

    public function test_create_form_offers_both_format_selections(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertSee('id="tournament_format" name="tournament_format" required', false)
            ->assertSee('id="finals_format" name="finals_format" required', false)
            ->assertSee('<option value="single_round_robin" >Single Round Robin</option>', false)
            ->assertSee('<option value="single_elimination" >Single Elimination</option>', false)
            ->assertSee('<option value="combination" >Combination Format</option>', false)
            ->assertSee('<option value="single_game" >Single Game</option>', false)
            ->assertSee('<option value="best_of_3" >Best-of-3</option>', false);
    }

    public function test_edit_form_preselects_saved_formats(): void
    {
        $tournament = Tournament::create($this->payload(['tournament_format' => 'combination', 'finals_format' => 'single_game']));

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $tournament))
            ->assertOk()
            ->assertSee('<option value="combination" selected>Combination Format</option>', false)
            ->assertSee('<option value="single_game" selected>Single Game</option>', false)
            ->assertDontSee('<option value="single_elimination" selected', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Persistence and editing
    |--------------------------------------------------------------------------
    */

    public function test_formats_are_saved_on_create(): void
    {
        $this->store()
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tournaments', [
            'name' => 'Format Cup',
            'tournament_format' => 'single_elimination',
            'finals_format' => 'best_of_3',
        ]);
    }

    public function test_every_format_option_is_accepted(): void
    {
        foreach (array_keys(Tournament::TOURNAMENT_FORMATS) as $i => $format) {
            foreach (array_keys(Tournament::FINALS_FORMATS) as $j => $finals) {
                $name = "Option Cup {$i}-{$j}";

                $this->store(['name' => $name, 'tournament_format' => $format, 'finals_format' => $finals])
                    ->assertSessionHasNoErrors();

                $this->assertDatabaseHas('tournaments', ['name' => $name, 'tournament_format' => $format, 'finals_format' => $finals]);
            }
        }
    }

    public function test_formats_can_be_changed_on_edit_without_touching_other_fields(): void
    {
        $tournament = Tournament::create($this->payload(['prize_pool' => '5000', 'venue' => 'Main Court']));

        $this->update($tournament, $this->payload([
            'tournament_format' => 'single_round_robin',
            'finals_format' => 'single_game',
        ]))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $tournament->refresh();
        $this->assertSame('single_round_robin', $tournament->tournament_format);
        $this->assertSame('single_game', $tournament->finals_format);
        $this->assertSame('5000.00', $tournament->prize_pool);
        $this->assertSame('Main Court', $tournament->venue);
        $this->assertSame('upcoming', $tournament->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    public function test_both_formats_are_required_on_create(): void
    {
        $payload = $this->payload();
        unset($payload['tournament_format'], $payload['finals_format']);

        $this->actingAs($this->admin)
            ->from(route('admin.tournaments.create'))
            ->post(route('admin.tournaments.store'), $payload)
            ->assertRedirect(route('admin.tournaments.create'))
            ->assertSessionHasErrors(['tournament_format', 'finals_format']);

        $this->store(['tournament_format' => '', 'finals_format' => ''])
            ->assertSessionHasErrors(['tournament_format', 'finals_format']);

        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_unknown_format_values_are_rejected(): void
    {
        $cases = [
            'tournament_format' => ['tournament_format' => 'double_elimination'],
            'finals_format' => ['finals_format' => 'best_of_5'],
        ];

        foreach ($cases as $field => $overrides) {
            $this->store($overrides)
                ->assertRedirect(route('admin.tournaments.create'))
                ->assertSessionHasErrors($field);
        }

        // Labels are not accepted in place of the stored values.
        $this->store(['tournament_format' => 'Single Round Robin', 'finals_format' => 'Best-of-3'])
            ->assertSessionHasErrors(['tournament_format', 'finals_format']);

        $this->assertDatabaseCount('tournaments', 0);

        $tournament = Tournament::create($this->payload());

        $this->update($tournament, $this->payload(['tournament_format' => 'swiss', 'name' => 'Should Not Save']))
            ->assertRedirect(route('admin.tournaments.edit', $tournament))
            ->assertSessionHasErrors('tournament_format');

        $this->assertSame('Format Cup', $tournament->fresh()->name);
        $this->assertSame('single_elimination', $tournament->fresh()->tournament_format);
    }

    public function test_only_admins_can_set_formats(): void
    {
        $tournament = Tournament::create($this->payload());

        foreach ([$this->manager, User::factory()->create(['role' => 'player'])] as $user) {
            $this->actingAs($user)
                ->post(route('admin.tournaments.store'), $this->payload(['name' => 'Sneaky Cup']))
                ->assertForbidden();
            $this->actingAs($user)
                ->put(route('admin.tournaments.update', $tournament), $this->payload(['tournament_format' => 'combination']))
                ->assertForbidden();
        }

        $this->assertDatabaseMissing('tournaments', ['name' => 'Sneaky Cup']);
        $this->assertSame('single_elimination', $tournament->fresh()->tournament_format);
    }

    /*
    |--------------------------------------------------------------------------
    | Manager display
    |--------------------------------------------------------------------------
    */

    public function test_managers_see_both_formats_on_open_tournament_details(): void
    {
        $tournament = Tournament::create($this->payload());

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $tournament))
            ->assertOk()
            ->assertSeeInOrder(['Tournament Format', 'Single Elimination'])
            ->assertSeeInOrder(['Finals Format', 'Best-of-3'])
            ->assertDontSee('Not specified')
            // Sprint 3 details are unchanged.
            ->assertSeeInOrder(['Prize Pool', 'Not announced'])
            ->assertSee('Request to participate');
    }

    /*
    |--------------------------------------------------------------------------
    | Existing tournaments (no formats stored)
    |--------------------------------------------------------------------------
    */

    public function test_existing_tournaments_without_formats_remain_accessible(): void
    {
        $legacy = $this->legacy();

        $this->assertNull($legacy->fresh()->tournament_format);
        $this->assertNull($legacy->fresh()->finals_format);
        $this->assertNull($legacy->tournamentFormatLabel());
        $this->assertNull($legacy->finalsFormatLabel());

        // Managers see a clear fallback instead of an error.
        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $legacy))
            ->assertOk()
            ->assertSeeInOrder(['Tournament Format', 'Not specified', 'Finals Format', 'Not specified']);

        $this->actingAs($this->manager)->get(route('manager.open-tournaments.index'))->assertOk()->assertSee('Legacy Cup');

        // Admin pages still load, with nothing preselected.
        $this->actingAs($this->admin)->get(route('admin.tournaments.index'))->assertOk()->assertSee('Legacy Cup');
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $legacy))
            ->assertOk()
            ->assertDontSee('<option value="single_round_robin" selected', false)
            ->assertDontSee('<option value="single_game" selected', false);

        // Managers can still apply under the existing rules.
        $this->actingAs($this->manager)
            ->post(route('manager.open-tournaments.apply', $legacy))
            ->assertSessionHas('success');
    }

    public function test_editing_an_existing_tournament_requires_choosing_formats(): void
    {
        $legacy = $this->legacy();

        $payload = $this->payload(['name' => 'Legacy Cup Renamed']);
        unset($payload['tournament_format'], $payload['finals_format']);

        $this->update($legacy, $payload)
            ->assertRedirect(route('admin.tournaments.edit', $legacy))
            ->assertSessionHasErrors(['tournament_format', 'finals_format']);

        $this->assertSame('Legacy Cup', $legacy->fresh()->name);
        $this->assertNull($legacy->fresh()->tournament_format);

        $this->update($legacy, $this->payload(['name' => 'Legacy Cup Renamed', 'tournament_format' => 'combination', 'finals_format' => 'single_game']))
            ->assertSessionHasNoErrors();

        $legacy->refresh();
        $this->assertSame('Legacy Cup Renamed', $legacy->name);
        $this->assertSame('combination', $legacy->tournament_format);
        $this->assertSame('single_game', $legacy->finals_format);
    }

    public function test_tournaments_work_before_the_formats_migration_is_applied(): void
    {
        $tournament = $this->legacy(['name' => 'Before Cup']);

        Schema::table('tournaments', fn ($table) => $table->dropColumn(['tournament_format', 'finals_format']));
        $this->assertFalse(Tournament::supportsFormats());

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertDontSee('name="tournament_format"', false)
            ->assertDontSee('name="finals_format"', false);

        // Formats are neither required nor written before the migration.
        $payload = $this->payload(['name' => 'Unmigrated Cup']);
        unset($payload['tournament_format'], $payload['finals_format']);

        $this->store([])->assertSessionHasNoErrors(); // submitted formats are ignored
        $this->actingAs($this->admin)
            ->post(route('admin.tournaments.store'), $payload)
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->update($tournament, ['name' => 'Before Cup Renamed'] + $payload)
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $created = Tournament::where('name', 'Unmigrated Cup')->firstOrFail();
        $this->assertArrayNotHasKey('tournament_format', $created->getAttributes());
        $this->assertSame('Before Cup Renamed', $tournament->fresh()->name);

        $this->actingAs($this->manager)
            ->get(route('manager.open-tournaments.show', $created))
            ->assertOk()
            ->assertSeeInOrder(['Tournament Format', 'Not specified', 'Finals Format', 'Not specified']);
    }
}

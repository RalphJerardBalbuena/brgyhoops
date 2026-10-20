<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * S3.9: optional prize pool (nullable decimal, Philippine pesos) on tournaments.
 */
class TournamentPrizePoolTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        // 2026-11-01 noon in Asia/Manila.
        $this->travelTo('2026-11-01 04:00:00');

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Prize Cup',
            'type' => 'inter_barangay',
            ...$this->formatFields(),
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-20',
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

    private function rawPrizePool(Tournament $tournament): ?string
    {
        return Tournament::whereKey($tournament->id)->value('prize_pool');
    }

    public function test_create_and_edit_forms_have_an_optional_prize_pool_field(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertSee('name="prize_pool"', false)
            ->assertSee('Prize pool (₱)');

        $tournament = Tournament::create($this->payload(['prize_pool' => '50000']));

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $tournament))
            ->assertOk()
            ->assertSee('name="prize_pool"', false)
            ->assertSee('value="50000.00"', false);
    }

    public function test_prize_pool_is_saved_on_create_and_blank_means_none(): void
    {
        $this->store(['prize_pool' => '50000.5'])
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $tournament = Tournament::where('name', 'Prize Cup')->firstOrFail();
        $this->assertEquals(50000.5, (float) $this->rawPrizePool($tournament));
        $this->assertSame('50000.50', $tournament->prize_pool);
        $this->assertSame('₱50,000.50', $tournament->formattedPrizePool());

        $this->store(['name' => 'No Prize Cup', 'prize_pool' => ''])
            ->assertSessionHasNoErrors();
        $this->store(['name' => 'Omitted Prize Cup'])
            ->assertSessionHasNoErrors();

        foreach (['No Prize Cup', 'Omitted Prize Cup'] as $name) {
            $tournament = Tournament::where('name', $name)->firstOrFail();
            $this->assertNull($this->rawPrizePool($tournament));
            $this->assertNull($tournament->formattedPrizePool());
        }
    }

    public function test_invalid_prize_pools_are_rejected(): void
    {
        $tournament = Tournament::create($this->payload(['name' => 'Existing Cup', 'prize_pool' => '1000']));

        foreach (['-1', '-0.01', 'abc', '12.345', '10000000000'] as $index => $value) {
            $this->store(['name' => "Bad Cup {$index}", 'prize_pool' => $value])
                ->assertRedirect(route('admin.tournaments.create'))
                ->assertSessionHasErrors('prize_pool');

            $this->assertDatabaseMissing('tournaments', ['name' => "Bad Cup {$index}"]);

            $this->update($tournament, $this->payload(['name' => 'Existing Cup', 'prize_pool' => $value]))
                ->assertRedirect(route('admin.tournaments.edit', $tournament))
                ->assertSessionHasErrors('prize_pool');

            $this->assertEquals(1000, (float) $this->rawPrizePool($tournament));
        }
    }

    public function test_boundary_amounts_are_accepted(): void
    {
        $this->store(['name' => 'Zero Cup', 'prize_pool' => '0'])->assertSessionHasNoErrors();
        $this->store(['name' => 'Max Cup', 'prize_pool' => '9999999999.99'])->assertSessionHasNoErrors();

        $this->assertSame('₱0.00', Tournament::where('name', 'Zero Cup')->firstOrFail()->formattedPrizePool());
        $this->assertSame('₱9,999,999,999.99', Tournament::where('name', 'Max Cup')->firstOrFail()->formattedPrizePool());
    }

    public function test_existing_tournaments_without_a_prize_pool_show_a_fallback_to_managers(): void
    {
        $manager = User::factory()->create(['role' => 'team_manager']);
        $legacy = Tournament::create($this->payload(['name' => 'Legacy Cup']));
        $zero = Tournament::create($this->payload(['name' => 'Zero Cup', 'prize_pool' => '0']));

        $this->assertNull($legacy->formattedPrizePool());

        $this->actingAs($manager)
            ->get(route('manager.open-tournaments.show', $legacy))
            ->assertOk()
            ->assertSeeInOrder(['Prize Pool', 'Not announced']);

        // An announced amount of zero is shown as an amount, not the fallback.
        $this->actingAs($manager)
            ->get(route('manager.open-tournaments.show', $zero))
            ->assertOk()
            ->assertSee('₱0.00')
            ->assertDontSee('Not announced');

        // Setting an amount later replaces the fallback.
        $this->update($legacy, $this->payload(['name' => 'Legacy Cup', 'prize_pool' => '20000']))
            ->assertSessionHasNoErrors();

        $this->actingAs($manager)
            ->get(route('manager.open-tournaments.show', $legacy))
            ->assertSee('₱20,000.00')
            ->assertDontSee('Not announced');
    }

    public function test_editing_changes_clears_or_preserves_the_prize_pool(): void
    {
        $tournament = Tournament::create($this->payload(['prize_pool' => '50000']));

        // Changed.
        $this->update($tournament, $this->payload(['prize_pool' => '75000.25']))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();
        $this->assertEquals(75000.25, (float) $this->rawPrizePool($tournament));

        // Omitted entirely: preserved.
        $this->update($tournament, $this->payload(['name' => 'Renamed Prize Cup']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Renamed Prize Cup', $tournament->fresh()->name);
        $this->assertEquals(75000.25, (float) $this->rawPrizePool($tournament));

        // Submitted blank: cleared.
        $this->update($tournament, $this->payload(['prize_pool' => '']))
            ->assertSessionHasNoErrors();
        $this->assertNull($this->rawPrizePool($tournament));
    }

    public function test_non_admins_cannot_set_prize_pools(): void
    {
        $tournament = Tournament::create($this->payload(['prize_pool' => '1000']));
        $manager = User::factory()->create(['role' => 'team_manager']);

        $this->actingAs($manager)
            ->post(route('admin.tournaments.store'), $this->payload(['name' => 'Sneaky Cup', 'prize_pool' => '1']))
            ->assertForbidden();
        $this->actingAs($manager)
            ->put(route('admin.tournaments.update', $tournament), $this->payload(['prize_pool' => '1']))
            ->assertForbidden();

        $this->assertDatabaseMissing('tournaments', ['name' => 'Sneaky Cup']);
        $this->assertEquals(1000, (float) $this->rawPrizePool($tournament));
    }

    public function test_tournaments_work_before_the_prize_pool_migration_is_applied(): void
    {
        $tournament = Tournament::create($this->payload(['name' => 'Before Cup']));
        $manager = User::factory()->create(['role' => 'team_manager']);

        Schema::table('tournaments', fn ($table) => $table->dropColumn('prize_pool'));
        $this->assertFalse(Tournament::supportsPrizePool());

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.create'))
            ->assertOk()
            ->assertDontSee('name="prize_pool"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.tournaments.edit', $tournament))
            ->assertOk()
            ->assertDontSee('name="prize_pool"', false);

        $this->store(['name' => 'Unmigrated Cup', 'prize_pool' => '5000'])
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $this->update($tournament, $this->payload(['name' => 'Before Cup Renamed', 'prize_pool' => '5000']))
            ->assertRedirect(route('admin.tournaments.index'))
            ->assertSessionHasNoErrors();

        $created = Tournament::where('name', 'Unmigrated Cup')->firstOrFail();
        $this->assertArrayNotHasKey('prize_pool', $created->getAttributes());
        $this->assertNull($created->formattedPrizePool());
        $this->assertSame('Before Cup Renamed', $tournament->fresh()->name);

        // Managers can still browse open tournaments; the prize pool shows the fallback.
        $this->actingAs($manager)
            ->get(route('manager.open-tournaments.index'))
            ->assertOk()
            ->assertSee('Unmigrated Cup')
            ->assertSeeInOrder(['Prize Pool', 'Not announced'])
            ->assertDontSee('₱');

        $this->actingAs($manager)
            ->get(route('manager.open-tournaments.show', $created))
            ->assertOk()
            ->assertSeeInOrder(['Prize Pool', 'Not announced'])
            ->assertDontSee('₱');
    }
}

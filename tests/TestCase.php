<?php

namespace Tests;

use App\Models\Team;
use App\Models\User;
use App\Support\RosterRules;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Valid tournament and finals formats for the admin tournament form.
     *
     * @return array{tournament_format: string, finals_format: string}
     */
    protected function formatFields(): array
    {
        return ['tournament_format' => 'single_round_robin', 'finals_format' => 'single_game'];
    }

    /**
     * Put approved players on a team's roster (defaults to the minimum size).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function fillRoster(Team $team, ?int $count = null)
    {
        $players = User::factory()
            ->count($count ?? RosterRules::minPlayers())
            ->create(['role' => 'player']);

        $team->players()->attach(
            $players->mapWithKeys(fn (User $player) => [$player->id => ['status' => 'approved']])->all()
        );

        return $players;
    }
}

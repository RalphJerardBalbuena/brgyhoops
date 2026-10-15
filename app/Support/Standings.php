<?php

namespace App\Support;

use App\Models\Tournament;

/**
 * Tournament standings, shared by the manager/admin standings page and the
 * player schedule page.
 *
 * Completed games with both scores count: 2 points for a win, 1 for a loss.
 * Sorted by points, then wins, then team name.
 */
class Standings
{
    /**
     * @return array<int, array{team: \App\Models\Team, played: int, wins: int, losses: int, points: int}>
     */
    public static function forTournament(Tournament $tournament): array
    {
        $teams = $tournament->teams()
            ->orderBy('name')
            ->get();

        $games = $tournament->games()
            ->where('status', 'completed')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->get();

        $standings = [];

        foreach ($teams as $team) {
            $standings[$team->id] = [
                'team' => $team,
                'played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points' => 0,
            ];
        }

        foreach ($games as $game) {
            if (!isset($standings[$game->home_team_id])) {
                continue;
            }

            if (!isset($standings[$game->away_team_id])) {
                continue;
            }

            $standings[$game->home_team_id]['played']++;
            $standings[$game->away_team_id]['played']++;

            if ($game->home_score > $game->away_score) {
                $standings[$game->home_team_id]['wins']++;
                $standings[$game->home_team_id]['points'] += 2;

                $standings[$game->away_team_id]['losses']++;
                $standings[$game->away_team_id]['points'] += 1;
            } elseif ($game->away_score > $game->home_score) {
                $standings[$game->away_team_id]['wins']++;
                $standings[$game->away_team_id]['points'] += 2;

                $standings[$game->home_team_id]['losses']++;
                $standings[$game->home_team_id]['points'] += 1;
            }
        }

        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }

            if ($a['wins'] !== $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }

            return $a['team']->name <=> $b['team']->name;
        });

        return $standings;
    }
}

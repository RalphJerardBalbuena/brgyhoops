<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class StandingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $activeTournamentId = session('active_tournament_id');

        if (!$activeTournamentId) {
            return redirect()->route('tournaments.index');
        }

        $activeTournament = $user->tournaments()
            ->where('tournaments.id', $activeTournamentId)
            ->first();

        if (!$activeTournament) {
            session()->forget('active_tournament_id');

            return redirect()->route('tournaments.index');
        }

        $teams = $activeTournament->teams()
            ->orderBy('name')
            ->get();

        $games = $activeTournament->games()
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

        return view('standings.index', compact(
            'activeTournament',
            'standings'
        ));
    }
}
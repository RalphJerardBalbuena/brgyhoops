@extends('layouts.app')

@section('title', 'Schedule & Standings')

@php
    $myTeamId = $myTeam?->id;
@endphp

@section('content')
    <x-page-header
        kicker="Player Portal"
        icon="fa-calendar-days"
        title="Schedule &"
        highlight="Standings"
        description="Games, results, and standings for the selected tournament."
    >
        @if ($activeTournament)
            <x-slot:meta>
                <span>
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    Viewing: <strong>{{ $activeTournament->name }}</strong>
                </span>
                <x-status-badge :status="$activeTournament->status" />
                @if ($myTeam)
                    <span>
                        <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                        Your team: <strong>{{ $myTeam->name }}</strong>
                    </span>
                @endif
            </x-slot:meta>
        @endif

        <a href="{{ route('player.teams.index') }}" class="button button-outline">
            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
            My Team
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <section class="page-section">
                @include('player._tournament-switcher', ['redirect' => 'schedule'])
            </section>

            @if ($activeTournament)
                <section class="page-section">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">Games &amp; Results</div>
                            <h2>Game Schedule</h2>
                        </div>
                    </div>

                    @if ($games->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                            </div>
                            <h3>No games yet</h3>
                            <p>No games have been scheduled for this tournament yet.</p>
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Home Team</th>
                                        <th>Away Team</th>
                                        <th class="num">Score</th>
                                        <th>Venue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($games as $game)
                                        @php
                                            $involvesMe = $myTeamId && in_array($myTeamId, [$game->home_team_id, $game->away_team_id], true);
                                        @endphp
                                        <tr @if ($involvesMe) data-my-game style="background: var(--red-soft);" @endif>
                                            <td>{{ $game->scheduled_at->format('M d, Y h:i A') }}</td>
                                            <td class="team-name">{{ $game->homeTeam->name }}</td>
                                            <td class="team-name">{{ $game->awayTeam->name }}</td>
                                            <td class="num">
                                                @if ($game->home_score !== null && $game->away_score !== null)
                                                    <span class="score">{{ $game->home_score }} - {{ $game->away_score }}</span>
                                                @else
                                                    <span class="muted">Not played</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $game->venue ?: 'TBA' }}
                                                @if ($directions = $game->coordinateDirectionsUrl() ?? \App\Support\GoogleMaps::directionsUrl($game->venue))
                                                    <br>
                                                    <a href="{{ $directions }}" class="card-link" target="_blank" rel="noopener noreferrer">
                                                        <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
                                                        Directions
                                                    </a>
                                                @endif
                                            </td>
                                            <td><x-status-badge :status="$game->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="page-section">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">Standings</div>
                            <h2>Tournament Standings</h2>
                            <p>Completed games only: 2 points for a win, 1 point for a loss.</p>
                        </div>
                    </div>

                    @if (empty($standings))
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-ranking-star" aria-hidden="true"></i>
                            </div>
                            <h3>No standings yet</h3>
                            <p>No teams are registered in this tournament yet.</p>
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="num">Rank</th>
                                        <th>Team</th>
                                        <th class="num">Played</th>
                                        <th class="num">Wins</th>
                                        <th class="num">Losses</th>
                                        <th class="num">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($standings as $index => $standing)
                                        <tr @if ($myTeamId === $standing['team']->id) data-my-standing style="background: var(--red-soft);" @endif>
                                            <td class="num"><span class="rank">{{ $index + 1 }}</span></td>
                                            <td class="team-name">{{ $standing['team']->name }}</td>
                                            <td class="num">{{ $standing['played'] }}</td>
                                            <td class="num">{{ $standing['wins'] }}</td>
                                            <td class="num">{{ $standing['losses'] }}</td>
                                            <td class="num"><span class="points">{{ $standing['points'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Tournament Standings')

@section('content')
    <x-page-header
        kicker="Standings & Results"
        icon="fa-ranking-star"
        title="Tournament"
        highlight="Standings"
        description="Rankings are based on completed games: 2 points for a win, 1 point for a loss."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <div class="button-row" style="margin-bottom: 24px;">
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('tournaments.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                        Change Tournament
                    </a>
                    <a href="{{ route('games.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        Game Schedule
                    </a>
                @else
                    <a href="{{ route('manager.dashboard') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('tournaments.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                        Change Tournament
                    </a>
                    <a href="{{ route('games.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        Game Schedule
                    </a>
                    <a href="{{ route('teams.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        Manage Teams
                    </a>
                @endif
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
                                <tr>
                                    <td class="num">
                                        <span class="rank">{{ $index + 1 }}</span>
                                    </td>

                                    <td class="team-name">
                                        {{ $standing['team']->name }}

                                        @if ($standing['team']->isEliminated())
                                            <span class="badge badge-cancelled">Eliminated</span>
                                        @elseif (auth()->user()->role === 'admin'
                                            && $activeTournament->status === 'ongoing'
                                            && $standing['team']->application_status === 'approved')
                                            <form method="POST"
                                                  action="{{ route('standings.teams.eliminate', $standing['team']) }}"
                                                  class="inline-form"
                                                  data-confirm="Mark {{ $standing['team']->name }} as officially eliminated? This cannot be undone here."
                                                  data-confirm-title="Eliminate this team?"
                                                  data-confirm-action="Mark eliminated"
                                                  data-confirm-variant="danger">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="button button-light button-sm" data-loading-text="Saving…">
                                                    Mark eliminated
                                                </button>
                                            </form>
                                        @endif
                                    </td>

                                    <td class="num">
                                        {{ $standing['played'] }}
                                    </td>

                                    <td class="num">
                                        {{ $standing['wins'] }}
                                    </td>

                                    <td class="num">
                                        {{ $standing['losses'] }}
                                    </td>

                                    <td class="num">
                                        <span class="points">{{ $standing['points'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

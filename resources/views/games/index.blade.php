@extends('layouts.app')

@section('title', 'Game Schedule')

@php
    $isAdmin = auth()->user()->role === 'admin';
@endphp

@section('content')
    <x-page-header
        kicker="Game Schedule"
        icon="fa-calendar-days"
        title="Game"
        highlight="Schedule"
        description="Matchups, dates, venues, and results for the selected tournament."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>

        @if ($isAdmin)
            <a href="{{ route('games.create') }}" class="button button-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Schedule New Game
            </a>
        @endif
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <div class="button-row" style="margin-bottom: 24px;">
                @if ($isAdmin)
                    <a href="{{ route('admin.dashboard') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        Back to Dashboard
                    </a>
                    <a href="{{ route('tournaments.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                        Change Tournament
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
                    <a href="{{ route('teams.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        Manage Teams
                    </a>
                @endif
                <a href="{{ route('standings.index') }}" class="button button-light button-sm">
                    <i class="fa-solid fa-ranking-star" aria-hidden="true"></i>
                    View Standings
                </a>
            </div>

            @if ($games->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                    </div>
                    <h3>No games yet</h3>
                    <p>No games have been scheduled for this tournament yet.</p>

                    @if ($isAdmin)
                        <a href="{{ route('games.create') }}" class="button button-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Schedule New Game
                        </a>
                    @endif
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
                                @if ($isAdmin)
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($games as $game)
                                <tr>
                                    <td>
                                        {{ $game->scheduled_at->format('M d, Y h:i A') }}
                                    </td>

                                    <td class="team-name">
                                        {{ $game->homeTeam->name }}
                                    </td>

                                    <td class="team-name">
                                        {{ $game->awayTeam->name }}
                                    </td>

                                    <td class="num">
                                        @if ($game->home_score !== null && $game->away_score !== null)
                                            <span class="score">{{ $game->home_score }} - {{ $game->away_score }}</span>
                                        @else
                                            <span class="muted">Not played</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $game->venue ?: 'TBA' }}
                                    </td>

                                    <td>
                                        <x-status-badge :status="$game->status" />
                                    </td>

                                    @if ($isAdmin)
                                        <td class="actions">
                                            <div class="button-row">
                                                <a href="{{ route('games.edit', $game) }}" class="button button-light button-sm">
                                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                    Edit
                                                </a>

                                                <form
                                                    action="{{ route('games.destroy', $game) }}"
                                                    method="POST"
                                                    class="inline-form"
                                                    data-confirm="Delete {{ $game->homeTeam->name }} vs {{ $game->awayTeam->name }} on {{ $game->scheduled_at->format('M d, Y') }}? This cannot be undone."
                                                    data-confirm-title="Delete this game?"
                                                    data-confirm-action="Delete game"
                                                    data-confirm-variant="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="button button-danger button-sm"
                                                        data-loading-text="Deleting…"
                                                    >
                                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

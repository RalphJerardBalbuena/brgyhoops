@extends('layouts.app')

@section('title', 'Tournaments')

@php
    $typeLabels = [
        'inter_zone' => 'Inter-Zone',
        'inter_barangay' => 'Inter-Barangay',
        'inter_town' => 'Inter-Town',
    ];
@endphp

@section('content')
    <x-page-header
        kicker="Tournament Administration"
        icon="fa-trophy"
        title="All"
        highlight="Tournaments"
        :description="'Welcome back, ' . auth()->user()->name . '. Review every competition, open an active tournament to manage its schedule, or create a new one. Several tournaments can run at the same time.'"
    >
        <x-slot:meta>
            <span>
                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                <strong>{{ $tournaments->count() }}</strong> available
            </span>
        </x-slot:meta>

        <a href="{{ route('admin.tournaments.create') }}" class="button button-primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Create Tournament
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <div class="section-heading">
                <div>
                    <div class="section-kicker">Competitions</div>
                    <h2>Tournaments</h2>
                    <p>Open an upcoming or ongoing tournament to manage it. Completed tournaments link to their public results.</p>
                </div>

                <span class="section-side-note">
                    <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    {{ $tournaments->count() }} available
                </span>
            </div>

            <section class="grid grid-3">
                @forelse ($tournaments as $tournament)
                    <article class="card card-hover card-ring">
                        <div class="card-top">
                            <div class="card-icon">
                                <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                            </div>

                            <x-status-badge :status="$tournament->status ?? 'unknown'" />
                        </div>

                        <h3 class="card-title">{{ $tournament->name }}</h3>

                        <div class="card-subtitle">
                            {{ $typeLabels[$tournament->type] ?? 'Basketball Tournament' }}
                        </div>

                        <p class="card-text" style="margin-top: 10px;">
                            {{ $tournament->description ?: 'No description has been provided for this tournament.' }}
                        </p>

                        <div class="details">
                            <div>
                                <div class="detail-label">Start Date</div>
                                <div class="detail-value">
                                    {{ $tournament->start_date ? $tournament->start_date->format('M d, Y') : 'Not set' }}
                                </div>
                            </div>

                            <div>
                                <div class="detail-label">End Date</div>
                                <div class="detail-value">
                                    {{ $tournament->end_date ? $tournament->end_date->format('M d, Y') : 'Not set' }}
                                </div>
                            </div>

                            <div>
                                <div class="detail-label">Teams</div>
                                <div class="detail-value">{{ $tournament->teams_count }}</div>
                            </div>

                            <div>
                                <div class="detail-label">Games</div>
                                <div class="detail-value">{{ $tournament->games_count }}</div>
                            </div>
                        </div>

                        @if ($tournament->venue)
                            <div class="note">
                                <strong>Venue:</strong>
                                {{ $tournament->venue }}
                            </div>
                        @endif

                        <div class="note">
                            <strong>Managers:</strong>
                            {{ $tournament->users->pluck('name')->join(', ') ?: 'No managers assigned yet' }}
                        </div>

                        <div class="card-footer">
                            <span class="card-text">
                                @if ($tournament->isManageable())
                                    Open this tournament's workspace.
                                @else
                                    View this tournament's results.
                                @endif
                            </span>

                            <div class="button-row">
                            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="button button-light button-sm">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                Edit
                            </a>

                            @if ($tournament->isManageable())
                                <form action="{{ route('tournaments.select', $tournament) }}" method="POST" class="inline-form">
                                    @csrf
                                    <button type="submit" class="button button-primary button-sm" data-loading-text="Opening…">
                                        Open Dashboard
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('public.tournament', $tournament) }}" class="button button-light button-sm">
                                    View Results
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <div class="empty-icon">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        </div>
                        <h3>No tournaments yet</h3>
                        <p>
                            No tournaments have been created yet. Create a tournament and
                            assign team managers so they can register their teams.
                        </p>
                        <a href="{{ route('admin.tournaments.create') }}" class="button button-primary">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Create Tournament
                        </a>
                    </div>
                @endforelse
            </section>
        </div>
    </div>
@endsection

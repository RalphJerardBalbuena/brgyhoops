@extends('layouts.app')

@section('title', 'Select Tournament')

@php
    $isAdmin = auth()->user()->role === 'admin';

    $typeLabels = [
        'inter_zone' => 'Inter-Zone',
        'inter_barangay' => 'Inter-Barangay',
        'inter_town' => 'Inter-Town',
    ];
@endphp

@section('content')
    <x-page-header
        kicker="Tournament Management"
        icon="fa-basketball"
        title="Select"
        highlight="Tournament"
        description="Choose a tournament to manage its teams, players, schedules, and other tournament activities. Several tournaments can run at the same time — switch between them here."
    >
        <x-slot:meta>
            <span>
                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                <strong>{{ $tournaments->count() }}</strong>
                {{ $tournaments->count() === 1 ? 'Tournament' : 'Tournaments' }}
                available
            </span>
        </x-slot:meta>

        <a href="{{ $isAdmin ? route('admin.dashboard') : url('/manager') }}" class="button button-outline">
            <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
            {{ $isAdmin ? 'Admin Dashboard' : 'Manager Dashboard' }}
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            @if ($tournaments->isEmpty())

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    </div>

                    <h3>No Tournaments Available</h3>

                    <p>
                        There are currently no tournaments assigned to your account.
                        Please contact your administrator if you think this is a mistake.
                    </p>
                </div>

            @else

                <section class="grid grid-3" aria-label="Available tournaments">

                    @foreach ($tournaments as $tournament)

                        @php
                            $isCurrent = $tournament->id === $activeTournamentId;
                        @endphp

                        <article class="card card-hover card-ring {{ $isCurrent ? 'card-current' : '' }}">

                            <div class="card-top">
                                <div class="card-icon">
                                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                                </div>

                                <x-status-badge :status="$tournament->status ?? 'upcoming'" />
                            </div>

                            <h2 class="card-title">{{ $tournament->name }}</h2>

                            @if ($tournament->type)
                                <div class="card-subtitle">
                                    {{ $typeLabels[$tournament->type] ?? 'Basketball Tournament' }}
                                </div>
                            @endif

                            @if ($tournament->description)
                                <p class="card-text" style="margin-top: 10px;">
                                    {{ $tournament->description }}
                                </p>
                            @else
                                <p class="card-text muted" style="margin-top: 10px;">
                                    No tournament description provided.
                                </p>
                            @endif

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
                            </div>

                            <div class="card-footer">
                                <span class="card-link">
                                    @if ($isCurrent)
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        Currently selected
                                    @else
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        Tournament
                                    @endif
                                </span>

                                <form method="POST"
                                      action="{{ route('tournaments.select', $tournament) }}"
                                      class="inline-form">
                                    @csrf

                                    <button type="submit" class="button {{ $isCurrent ? 'button-dark' : 'button-primary' }} button-sm" data-loading-text="Opening…">
                                        {{ $isCurrent ? 'Continue' : 'Select' }}
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>

                        </article>

                    @endforeach

                </section>

            @endif

            <div class="section-heading" style="margin-top: 36px; margin-bottom: 0; align-items: center;">
                <p class="section-side-note">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Select the tournament you are responsible for managing.
                </p>

                <a href="{{ $isAdmin ? route('admin.dashboard') : url('/manager') }}" class="button button-light button-sm">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection

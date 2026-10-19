@extends('layouts.app')

@section('title', 'Open Tournaments')

@section('content')
    <x-page-header
        kicker="Team Manager"
        icon="fa-door-open"
        title="Open"
        highlight="Tournaments"
        description="Browse upcoming tournaments created by the league administrators. Taking part requires admin approval — viewing a tournament does not register your team."
    >
        <x-slot:meta>
            <span>
                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                <strong>{{ $tournaments->count() }}</strong>
                {{ $tournaments->count() === 1 ? 'Tournament' : 'Tournaments' }}
                open
            </span>
        </x-slot:meta>

        <a href="{{ route('tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
            My Tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            @if ($tournaments->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-door-closed" aria-hidden="true"></i>
                    </div>

                    <h3>No Open Tournaments</h3>

                    <p>
                        There are no upcoming tournaments right now. Check back later
                        for new competitions announced by the administrators.
                    </p>
                </div>
            @else
                <section class="grid grid-3" aria-label="Open tournaments">
                    @foreach ($tournaments as $tournament)
                        @php
                            $isAssigned = in_array($tournament->id, $assignedIds, true);
                            $prizePool = $tournament->formattedPrizePool();
                            $application = $applications->get($tournament->id);
                        @endphp

                        <article class="card card-hover card-ring">
                            <div class="card-top">
                                <div class="card-icon">
                                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                </div>

                                <div class="button-row">
                                    @if ($isAssigned)
                                        <span class="badge badge-dark">Assigned</span>
                                    @endif
                                    @if ($application)
                                        <span class="badge badge-{{ $application->badgeClass() }}">{{ $application->status === 'conflict' ? 'Schedule conflict' : 'Application ' . $application->status }}</span>
                                    @endif
                                    <x-status-badge :status="$tournament->status" />
                                </div>
                            </div>

                            <h2 class="card-title">{{ $tournament->name }}</h2>

                            <div class="card-subtitle">{{ $tournament->typeLabel() }}</div>

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

                                @if ($prizePool)
                                    <div>
                                        <div class="detail-label">Prize Pool</div>
                                        <div class="detail-value">{{ $prizePool }}</div>
                                    </div>
                                @endif
                            </div>

                            @if (filled($tournament->venue))
                                <div class="note">
                                    <strong>Venue:</strong>
                                    {{ $tournament->venue }}
                                </div>
                            @endif

                            <div class="card-footer">
                                <a href="{{ route('manager.open-tournaments.show', $tournament) }}" class="button button-light button-sm">
                                    View details
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>

                                @if ($isAssigned)
                                    <form method="POST" action="{{ route('tournaments.select', $tournament) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="button button-primary button-sm" data-loading-text="Opening…">
                                            Open workspace
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        </div>
    </div>
@endsection

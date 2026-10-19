@extends('layouts.app')

@section('title', $tournament->name)

@php
    $prizePool = $tournament->formattedPrizePool();
@endphp

@section('content')
    <x-page-header
        kicker="Open Tournament"
        icon="fa-door-open"
        :title="$tournament->name"
        :description="$tournament->typeLabel() . ' tournament. Review the details below before requesting to take part.'"
    >
        <x-slot:meta>
            <x-status-badge :status="$tournament->status" />

            @if ($isAssigned)
                <span class="badge badge-dark">Assigned</span>
            @endif
        </x-slot:meta>

        <a href="{{ route('manager.open-tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to open tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <section class="page-section">
                <div class="card card-ring">
                    <h2 class="card-title">Tournament details</h2>

                    <div class="details">
                        <div>
                            <div class="detail-label">Type</div>
                            <div class="detail-value">{{ $tournament->typeLabel() }}</div>
                        </div>

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

                        @if (filled($tournament->venue))
                            <div>
                                <div class="detail-label">Venue</div>
                                <div class="detail-value">{{ $tournament->venue }}</div>
                            </div>
                        @endif
                    </div>

                    @if ($tournament->description)
                        <p class="card-text" style="margin-top: 16px;">{{ $tournament->description }}</p>
                    @endif
                </div>
            </section>

            @if (filled($tournament->venue))
                <section class="page-section">
                    <x-venue-map :venue="$tournament->venue" />
                </section>
            @endif

            <section class="page-section">
                <div class="card card-ring">
                    @if ($isAssigned)
                        <h2 class="card-title">You are assigned to this tournament</h2>

                        <p class="card-text">
                            An administrator has already given you access to this tournament's workspace.
                        </p>

                        <div class="card-footer">
                            <form method="POST" action="{{ route('tournaments.select', $tournament) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="button button-primary" data-loading-text="Opening…">
                                    Open workspace
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <h2 class="card-title">Take part in this tournament</h2>

                        <p class="card-text">
                            Participation requires admin approval. Applications will open in a later update.
                        </p>

                        <div class="card-footer">
                            <button type="button" class="button button-primary" disabled aria-disabled="true">
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                Request to participate
                            </button>

                            <a href="{{ route('public.tournament', $tournament) }}" class="button button-light">
                                View public page
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

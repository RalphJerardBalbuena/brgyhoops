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

            @if ($application)
                <span class="badge badge-{{ $application->badgeClass() }}">{{ $application->status === 'conflict' ? 'Schedule conflict' : 'Application ' . $application->status }}</span>
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
                    @elseif ($application?->status === 'pending')
                        <h2 class="card-title">Application pending</h2>

                        <p class="card-text">
                            Your application is waiting for an administrator's review. You will get access to
                            this tournament's workspace only if it is approved.
                        </p>
                    @elseif ($application?->status === 'rejected')
                        <h2 class="card-title">Application rejected</h2>

                        <p class="card-text">
                            An administrator rejected your application to take part in this tournament.
                        </p>

                        @if ($application->admin_review_note)
                            <div class="note">
                                Reason: {{ $application->admin_review_note }}
                            </div>
                        @endif
                    @elseif ($application?->status === 'conflict')
                        <h2 class="card-title">Application closed: schedule conflict</h2>

                        <p class="card-text">
                            This application was closed because another tournament you applied to, with
                            overlapping dates, was approved. You can apply again if you become eligible,
                            for example once your team has been eliminated from that tournament.
                        </p>

                        @if ($application->admin_review_note)
                            <div class="note">
                                Reason: {{ $application->admin_review_note }}
                            </div>
                        @endif

                        @if ($reapplyProblems === [])
                            <p class="card-text" style="margin-top: 12px;">
                                You are eligible again, so you can resubmit this application. It still needs
                                an administrator's approval.
                            </p>

                            <div class="card-footer">
                                <form method="POST" action="{{ route('manager.open-tournaments.apply', $tournament) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="button button-primary" data-loading-text="Submitting…">
                                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                        Apply again
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="note" style="border-left-color: var(--amber);">
                                <strong>You cannot apply again yet.</strong>
                                {{ implode(' ', $reapplyProblems) }}
                            </div>
                        @endif
                    @else
                        <h2 class="card-title">Take part in this tournament</h2>

                        <p class="card-text">
                            Participation requires admin approval. Submitting a request does not give you
                            access to the tournament until an administrator approves it.
                        </p>

                        <div class="card-footer">
                            <form method="POST" action="{{ route('manager.open-tournaments.apply', $tournament) }}" class="inline-form">
                                @csrf
                                <button type="submit" class="button button-primary" data-loading-text="Submitting…">
                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                    Request to participate
                                </button>
                            </form>

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

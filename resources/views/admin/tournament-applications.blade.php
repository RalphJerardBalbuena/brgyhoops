@extends('layouts.app')

@section('title', 'Manager Applications')

@section('content')
    <x-page-header
        kicker="Tournament Administration"
        icon="fa-file-signature"
        title="Manager"
        highlight="Applications"
        description="Review team managers' requests to take part in upcoming tournaments. Approving an application gives the manager access to the tournament's workspace. Team roster submissions are reviewed separately under Applications."
    >
        <a href="{{ route('admin.tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
            Tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <section class="grid grid-3 page-section">
                <div class="card stat-card">
                    <div class="card-icon" style="background: var(--amber-soft); color: var(--amber);">
                        <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="stat-number">{{ $applications->where('status', 'pending')->count() }}</span>
                        <span class="stat-label">Pending Review</span>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="card-icon" style="background: var(--green-soft); color: var(--green);">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="stat-number">{{ $applications->where('status', 'approved')->count() }}</span>
                        <span class="stat-label">Approved</span>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="stat-number">{{ $applications->where('status', 'rejected')->count() }}</span>
                        <span class="stat-label">Rejected</span>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Applications</div>
                        <h2>Tournament Requests</h2>
                        <p>Pending applications are listed first.</p>
                    </div>
                </div>

                <div class="grid grid-2">
                    @forelse ($applications as $application)
                        @php
                            $tournament = $application->tournament;
                            $isPending = $application->status === 'pending';
                            $canApprove = $isPending && \App\Support\TournamentApplications::approvalProblems($application) === [];
                        @endphp

                        <article class="card">
                            <div class="card-top" style="align-items: flex-start;">
                                <div>
                                    <h3 class="card-title">{{ $application->manager?->name ?? 'Unknown manager' }}</h3>
                                    <div class="card-subtitle">{{ $application->manager?->email }}</div>
                                </div>

                                <span class="badge badge-{{ $application->badgeClass() }}">
                                    {{ $application->statusLabel() }}
                                </span>
                            </div>

                            <div class="details">
                                <div>
                                    <div class="detail-label">Tournament</div>
                                    <div class="detail-value">{{ $tournament?->name ?? 'Unknown' }}</div>
                                </div>

                                <div>
                                    <div class="detail-label">Tournament Dates</div>
                                    <div class="detail-value">
                                        @if ($tournament?->start_date && $tournament?->end_date)
                                            {{ $tournament->start_date->format('M d, Y') }} – {{ $tournament->end_date->format('M d, Y') }}
                                        @else
                                            Not set
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="detail-label">Applied</div>
                                    <div class="detail-value">{{ $application->created_at?->format('M d, Y h:i A') ?? 'Not recorded' }}</div>
                                </div>

                                @if ($application->reviewed_at)
                                    <div>
                                        <div class="detail-label">Reviewed</div>
                                        <div class="detail-value">{{ $application->reviewed_at->format('M d, Y h:i A') }}</div>
                                    </div>
                                @endif
                            </div>

                            @if ($application->admin_review_note)
                                <div class="note">
                                    Review note: {{ $application->admin_review_note }}
                                </div>
                            @endif

                            @if ($isPending)
                                @unless ($canApprove)
                                    <div class="note" style="border-left-color: var(--amber);">
                                        {{ implode(' ', \App\Support\TournamentApplications::approvalProblems($application)) }}
                                    </div>
                                @endunless

                                <div class="grid grid-2" style="margin-top: 22px;">
                                    @if ($canApprove)
                                        <form method="POST"
                                              action="{{ route('admin.tournament-applications.approve', $application) }}"
                                              class="field"
                                              data-confirm="Approve {{ $application->manager?->name }} for {{ $tournament?->name }}? The manager will get access to the tournament's workspace."
                                              data-confirm-title="Approve this application?"
                                              data-confirm-action="Approve application">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                class="button button-success button-sm"
                                                type="submit"
                                                data-loading-text="Approving…">
                                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                Approve Application
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                          action="{{ route('admin.tournament-applications.reject', $application) }}"
                                          class="field"
                                          data-confirm="Reject {{ $application->manager?->name }}'s application for {{ $tournament?->name }}?"
                                          data-confirm-title="Reject this application?"
                                          data-confirm-action="Reject application"
                                          data-confirm-variant="danger">
                                        @csrf
                                        @method('PATCH')

                                        <label for="reject-note-{{ $application->id }}">
                                            Reason for rejection <span class="optional">(optional)</span>
                                        </label>

                                        <textarea
                                            id="reject-note-{{ $application->id }}"
                                            name="admin_review_note"
                                            maxlength="1000"
                                            placeholder="Optionally explain why this application is rejected..."></textarea>

                                        <button
                                            class="button button-danger button-sm"
                                            type="submit"
                                            data-loading-text="Rejecting…">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            Reject Application
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="card-footer">
                                    <span class="card-text">This application has been reviewed.</span>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <div class="empty-icon">
                                <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
                            </div>
                            <h3>No manager applications yet</h3>
                            <p>When a team manager requests to take part in an upcoming tournament, it will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

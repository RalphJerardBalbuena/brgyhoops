@extends('layouts.app')

@section('title', 'Team Applications')

@section('content')
    <x-page-header
        kicker="Tournament Administration"
        icon="fa-clipboard-check"
        title="Team"
        highlight="Applications"
        description="Review teams submitted by managers and decide whether to admit them into the tournament."
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
                        <span class="stat-number">{{ $applications->where('application_status', 'pending')->count() }}</span>
                        <span class="stat-label">Pending Review</span>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="card-icon" style="background: var(--green-soft); color: var(--green);">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="stat-number">{{ $applications->where('application_status', 'approved')->count() }}</span>
                        <span class="stat-label">Approved</span>
                    </div>
                </div>

                <div class="card stat-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="stat-number">{{ $applications->where('application_status', 'rejected')->count() }}</span>
                        <span class="stat-label">Rejected</span>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Applications</div>
                        <h2>Submitted Teams</h2>
                        <p>Pending applications are listed first.</p>
                    </div>
                </div>

                <div class="grid grid-2">
                    @forelse ($applications as $team)
                        @php
                            $status = $team->application_status ?? 'pending';

                            $statusClass = in_array($status, ['pending', 'approved', 'rejected'], true)
                                ? $status
                                : 'pending';

                            // The model casts these fields to datetime. This fallback also
                            // safely handles a raw string if one is supplied unexpectedly.
                            $appliedAt = $team->applied_at;

                            if (is_string($appliedAt) && trim($appliedAt) !== '') {
                                try {
                                    $appliedAt = \Illuminate\Support\Carbon::parse($appliedAt);
                                } catch (\Throwable $exception) {
                                    $appliedAt = null;
                                }
                            }
                        @endphp

                        <article class="card">
                            <div class="card-top" style="align-items: flex-start;">
                                <div>
                                    <h3 class="card-title">{{ $team->name }}</h3>

                                    @if ($team->short_name)
                                        <div class="card-subtitle">{{ $team->short_name }}</div>
                                    @endif
                                </div>

                                <span class="badge badge-{{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>

                            <p class="card-text">
                                {{ $team->description ?: 'No description provided.' }}
                            </p>

                            <div class="details">
                                <div>
                                    <div class="detail-label">Manager</div>
                                    <div class="detail-value">{{ $team->manager?->name ?? 'Unknown' }}</div>
                                </div>

                                <div>
                                    <div class="detail-label">Tournament</div>
                                    <div class="detail-value">{{ $team->tournament?->name ?? 'Unknown' }}</div>
                                </div>

                                <div>
                                    <div class="detail-label">Applied</div>
                                    <div class="detail-value">
                                        @if ($appliedAt instanceof \DateTimeInterface)
                                            {{ $appliedAt->format('M d, Y h:i A') }}
                                        @else
                                            Not recorded
                                        @endif
                                    </div>
                                </div>

                                @if ($team->reviewed_at)
                                    <div>
                                        <div class="detail-label">Reviewed</div>
                                        <div class="detail-value">
                                            {{ $team->reviewed_at instanceof \DateTimeInterface
                                                ? $team->reviewed_at->format('M d, Y h:i A')
                                                : \Illuminate\Support\Carbon::parse($team->reviewed_at)->format('M d, Y h:i A') }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div style="margin-top: 16px;">
                                <div class="detail-label">
                                    Roster ({{ $team->roster->count() }} / {{ \App\Support\RosterRules::maxPlayers() }} players, min {{ \App\Support\RosterRules::minPlayers() }})
                                </div>

                                @if ($team->roster->isEmpty())
                                    <p class="card-text">No players on the roster.</p>
                                @else
                                    <ul class="roster-list">
                                        @foreach ($team->roster as $player)
                                            <li>
                                                <span class="avatar avatar-sm">{{ \Illuminate\Support\Str::substr($player->name ?? 'P', 0, 1) }}</span>
                                                {{ $player->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($status === 'pending' && ($rosterProblems = \App\Support\RosterRules::approvalProblems($team)))
                                    <div class="note" style="border-left-color: var(--amber);">
                                        {{ implode(' ', $rosterProblems) }} Reject with a reason so the manager can fix it.
                                    </div>
                                @endif
                            </div>

                            @if ($team->admin_review_note)
                                <div class="note">
                                    Review note: {{ $team->admin_review_note }}
                                </div>
                            @endif

                            @if ($status === 'pending')
                                <div class="grid grid-2" style="margin-top: 22px;">
                                    <form method="POST"
                                          action="{{ route('admin.team-applications.approve', $team) }}"
                                          class="field"
                                          data-confirm="Approve {{ $team->name }}? Its roster will be locked and the team can be scheduled for games."
                                          data-confirm-title="Approve this team?"
                                          data-confirm-action="Approve team">
                                        @csrf
                                        @method('PATCH')

                                        <label for="approve-note-{{ $team->id }}">
                                            Optional approval note
                                        </label>

                                        <textarea
                                            id="approve-note-{{ $team->id }}"
                                            name="admin_review_note"
                                            maxlength="1000"
                                            placeholder="Add an optional note..."></textarea>

                                        <button
                                            class="button button-success button-sm"
                                            type="submit"
                                            data-loading-text="Approving…">
                                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                                            Approve Team
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('admin.team-applications.reject', $team) }}"
                                          class="field"
                                          data-confirm="Reject {{ $team->name }}? The manager will see your reason and can correct and resubmit the team."
                                          data-confirm-title="Reject this team?"
                                          data-confirm-action="Reject team"
                                          data-confirm-variant="danger">
                                        @csrf
                                        @method('PATCH')

                                        <label for="reject-note-{{ $team->id }}">
                                            Reason for rejection
                                        </label>

                                        <textarea
                                            id="reject-note-{{ $team->id }}"
                                            name="admin_review_note"
                                            maxlength="1000"
                                            required
                                            placeholder="Explain why this application is rejected..."></textarea>

                                        <button
                                            class="button button-danger button-sm"
                                            type="submit"
                                            data-loading-text="Rejecting…">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            Reject Team
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="card-footer">
                                    <span class="card-text">This application has been reviewed.</span>

                                    <a href="{{ route('admin.team-applications.index') }}"
                                       class="button button-light button-sm">
                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                        Refresh Applications
                                    </a>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="empty-state" style="grid-column: 1 / -1;">
                            <div class="empty-icon">
                                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                            </div>
                            <h3>No team applications yet</h3>
                            <p>When a manager submits a team for approval, it will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

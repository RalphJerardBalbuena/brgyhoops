{{--
    Team roster + application status for the team's manager.
    Expects: $team (with `roster` loaded).
--}}
@php
    $rosterCount = $team->roster->count();
    $minPlayers = \App\Support\RosterRules::minPlayers();
    $maxPlayers = \App\Support\RosterRules::maxPlayers();
    $submissionProblems = \App\Support\RosterRules::submissionProblems($team);
    $applicationStatus = $team->application_status ?? 'draft';
@endphp

<div class="details">
    <div>
        <div class="detail-label">Application</div>
        <div class="detail-value"><x-status-badge :status="$applicationStatus" /></div>
    </div>

    <div>
        <div class="detail-label">Roster</div>
        <div class="detail-value">
            {{ $rosterCount }} / {{ $maxPlayers }} players
            <span class="muted">(min {{ $minPlayers }})</span>
        </div>
    </div>
</div>

@if ($applicationStatus === 'rejected' && $team->admin_review_note)
    <div class="note">
        <strong>Admin feedback:</strong> {{ $team->admin_review_note }}
    </div>
@endif

<div style="margin-top: 16px;">
    <div class="detail-label">Players</div>

    @if ($team->roster->isEmpty())
        <p class="card-text">
            No players on the roster yet. Approve player requests to build your roster.
        </p>
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
</div>

<div class="note" style="border-left-color: {{ $submissionProblems ? 'var(--amber)' : 'var(--green)' }};">
    @if ($applicationStatus === 'pending')
        Submitted {{ $team->applied_at ? \Illuminate\Support\Carbon::parse($team->applied_at)->format('M d, Y h:i A') : '' }} — awaiting admin review. The roster is locked until the review is complete.
    @elseif ($applicationStatus === 'approved')
        Approved — this team can be scheduled for games. The roster is closed.
    @elseif ($submissionProblems)
        {{ implode(' ', $submissionProblems) }}
    @else
        Roster complete — ready to submit for admin approval.
    @endif
</div>

@if (in_array($applicationStatus, \App\Support\RosterRules::EDITABLE_STATUSES, true))
    <form method="POST" action="{{ route('teams.submit', $team) }}" class="inline-form" style="margin-top: 14px;"
          data-confirm="Submit {{ $team->name }} and its roster for admin approval? The roster will be locked during review."
          data-confirm-title="Submit for approval?"
          data-confirm-action="Submit team">
        @csrf
        @method('PATCH')
        <button type="submit" class="button button-primary button-sm" data-loading-text="Submitting…" @disabled($submissionProblems !== [])>
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            {{ $applicationStatus === 'rejected' ? 'Resubmit for Approval' : 'Submit for Approval' }}
        </button>
    </form>
@endif

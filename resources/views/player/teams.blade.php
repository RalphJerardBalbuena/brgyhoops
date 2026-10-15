@extends('layouts.app')

@section('title', 'My Team')

@php
    $pendingCount = $myRequests->filter(fn ($team) => $team->pivot->status === 'pending')->count();
    $approvedCount = $myTeam ? 1 : 0;
    $requestHistory = $myRequests->filter(fn ($team) => in_array($team->pivot->status, ['pending', 'rejected'], true));
@endphp

@section('content')
    <x-page-header
        kicker="Player Portal"
        icon="fa-basketball"
        title="Find Your"
        highlight="Team."
        description="Your next game starts here. Explore teams in upcoming and ongoing tournaments, send a join request, and track your application right here."
    >
        @if ($activeTournament)
            <x-slot:meta>
                <span>
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    Viewing: <strong>{{ $activeTournament->name }}</strong>
                </span>
                <x-status-badge :status="$activeTournament->status" />
            </x-slot:meta>
        @endif

        <a href="{{ route('player.schedule') }}" class="button button-outline">
            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
            Schedule &amp; Standings
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <section class="page-section">
                @include('player._tournament-switcher')
            </section>

            @if ($activeTournament)
                <section class="grid grid-3 page-section">
                    <div class="card stat-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span class="stat-number">{{ $teams->count() }}</span>
                            <span class="stat-label">Available teams</span>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="card-icon" style="background: var(--amber-soft); color: var(--amber);">
                            <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span class="stat-number">{{ $pendingCount }}</span>
                            <span class="stat-label">Pending requests</span>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="card-icon" style="background: var(--green-soft); color: var(--green);">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        </div>
                        <div>
                            <span class="stat-number">{{ $approvedCount }}</span>
                            <span class="stat-label">Approved requests</span>
                        </div>
                    </div>
                </section>

                {{-- My Team: from the approved player_team row --}}
                <section class="page-section" id="my-team">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">My Team</div>
                            <h2>{{ $myTeam ? $myTeam->name : 'No Team Yet' }}</h2>
                            <p>Your team in {{ $activeTournament->name }}.</p>
                        </div>
                    </div>

                    @if ($myTeam)
                        <div class="card card-current" data-my-team>
                            <div class="card-top">
                                <div class="card-icon">
                                    <i class="fa-solid fa-shirt" aria-hidden="true"></i>
                                </div>
                                <span class="badge badge-approved">You're on this roster</span>
                            </div>

                            <h3 class="card-title">{{ $myTeam->name }}</h3>

                            @if ($myTeam->short_name)
                                <div class="card-subtitle">{{ $myTeam->short_name }}</div>
                            @endif

                            @if ($myTeam->description)
                                <p class="card-text" style="margin-top: 10px;">{{ $myTeam->description }}</p>
                            @endif

                            <div class="details">
                                <div>
                                    <div class="detail-label">Team manager</div>
                                    <div class="detail-value">{{ $myTeam->manager->name ?? 'Not assigned' }}</div>
                                </div>
                                <div>
                                    <div class="detail-label">Team status</div>
                                    <div class="detail-value"><x-status-badge :status="$myTeam->application_status ?? 'draft'" /></div>
                                </div>
                            </div>

                            <div style="margin-top: 16px;">
                                <div class="detail-label">Roster ({{ $myTeam->roster->count() }} {{ $myTeam->roster->count() === 1 ? 'player' : 'players' }})</div>
                                <ul class="roster-list">
                                    @foreach ($myTeam->roster as $teammate)
                                        <li>
                                            <span class="avatar avatar-sm">{{ \Illuminate\Support\Str::substr($teammate->name ?? 'P', 0, 1) }}</span>
                                            {{ $teammate->name }}
                                            @if ($teammate->id === auth()->id())
                                                <span class="badge badge-dark badge-plain">You</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="card-footer">
                                <span class="card-text">You can only play for one team per tournament.</span>
                                <a href="{{ route('player.schedule') }}" class="button button-light button-sm">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    Team schedule
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            </div>
                            <h3>You're not on a team yet</h3>
                            <p>
                                @if ($readOnly)
                                    You did not play in {{ $activeTournament->name }}.
                                @else
                                    Send a join request to one of the teams below. Once a team manager approves it, your team and roster appear here.
                                @endif
                            </p>
                        </div>
                    @endif
                </section>

                {{-- Pending / rejected requests in this tournament --}}
                @if ($requestHistory->isNotEmpty())
                    <section class="page-section">
                        <div class="section-heading">
                            <div>
                                <div class="section-kicker">My Requests</div>
                                <h2>Join Requests</h2>
                                <p>Your requests to teams in {{ $activeTournament->name }}.</p>
                            </div>
                        </div>

                        <div class="card card-flush">
                            <div class="list">
                                @foreach ($requestHistory as $requestedTeam)
                                    <div class="list-row" @if ($loop->first) style="border-top: 0;" @endif>
                                        <div class="list-row-main">
                                            <div class="avatar">{{ \Illuminate\Support\Str::substr($requestedTeam->name, 0, 1) }}</div>
                                            <div>
                                                <div class="list-title">{{ $requestedTeam->name }}</div>
                                                <div class="list-caption">
                                                    @if ($requestedTeam->pivot->status === 'pending')
                                                        Waiting for the manager to review your request.
                                                    @elseif ($myTeam)
                                                        Not approved — you are already on {{ $myTeam->name }}.
                                                    @else
                                                        Not approved. You can request again if the team is still accepting players.
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <x-status-badge :status="$requestedTeam->pivot->status" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                {{-- Teams accepting players (only when not already on a team, tournament open) --}}
                @unless ($myTeam || $readOnly)
                    <section class="page-section">
                        <div class="section-heading">
                            <div>
                                <div class="section-kicker">Explore Teams</div>
                                <h2>Teams Looking for Players</h2>
                            </div>

                            <span class="section-side-note">
                                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                                {{ $teams->count() }} {{ $teams->count() === 1 ? 'TEAM' : 'TEAMS' }}
                            </span>
                        </div>

                        @if ($teams->isEmpty())
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                </div>
                                <h3>No teams available right now</h3>
                                <p>There are no teams listed in upcoming or ongoing tournaments at the moment. Check back later when managers create their teams.</p>
                            </div>
                        @else
                            <div class="grid grid-3">
                                @foreach ($teams as $team)
                                    @php
                                        $myRequest = $myRequests->get($team->id);
                                        $requestStatus = $myRequest ? $myRequest->pivot->status : null;
                                    @endphp

                                    <article class="card card-hover card-ring">
                                        <div class="card-top">
                                            <div class="card-icon">
                                                <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                            </div>

                                            @if ($requestStatus === 'pending')
                                                <span class="badge badge-pending">Pending</span>
                                            @elseif ($requestStatus === 'approved')
                                                <span class="badge badge-approved">Approved</span>
                                            @elseif ($requestStatus === 'rejected')
                                                <span class="badge badge-rejected">Rejected</span>
                                            @endif
                                        </div>

                                        <h3 class="card-title">{{ $team->name }}</h3>

                                        <div class="card-subtitle">
                                            {{ $team->short_name ?: 'Community basketball team' }}
                                        </div>

                                        <p class="card-text" style="margin-top: 10px;">
                                            <i class="fa-solid fa-trophy text-red" aria-hidden="true"></i>
                                            {{ $team->tournament->name ?? 'Tournament not assigned' }}
                                        </p>

                                        <p class="card-text" style="margin-top: 8px;">
                                            {{ $team->description ?: 'Join this team and take part in the community basketball action.' }}
                                        </p>

                                        <div class="details">
                                            <div>
                                                <div class="detail-label">Team manager</div>
                                                <div class="detail-value">{{ $team->manager->name ?? 'Not assigned' }}</div>
                                            </div>

                                            <div>
                                                <div class="detail-label">Team roster</div>
                                                <div class="detail-value">{{ $team->approved_players_count }} {{ $team->approved_players_count === 1 ? 'player' : 'players' }}</div>
                                            </div>
                                        </div>

                                        <div class="card-footer">
                                            @if ($requestStatus === 'pending')
                                                <span class="card-text">
                                                    <i class="fa-solid fa-hourglass-half" style="color: var(--amber);" aria-hidden="true"></i>
                                                    Waiting for the manager to review your request.
                                                </span>
                                            @elseif ($requestStatus === 'rejected')
                                                <span class="card-text">
                                                    <i class="fa-solid fa-circle-xmark text-red" aria-hidden="true"></i>
                                                    Your previous request was rejected.
                                                </span>

                                                <form method="POST" action="{{ route('player.teams.request', $team) }}" class="inline-form" data-confirm="Send a new request to join {{ $team->name }}?" data-confirm-title="Request to join again?" data-confirm-action="Send request">
                                                    @csrf
                                                    <button type="submit" class="button button-dark button-sm" data-loading-text="Sending…">
                                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                                                        Try Again
                                                    </button>
                                                </form>
                                            @elseif ($team->approved_players_count >= \App\Support\RosterRules::maxPlayers())
                                                <span class="card-text">
                                                    <i class="fa-solid fa-users-slash" aria-hidden="true"></i>
                                                    Roster full — not accepting requests.
                                                </span>
                                            @else
                                                <span class="card-link">
                                                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                                                    Open for applications
                                                </span>

                                                <form method="POST" action="{{ route('player.teams.request', $team) }}" class="inline-form" data-confirm="Send a request to join {{ $team->name }}? The team manager will review it." data-confirm-title="Request to join?" data-confirm-action="Send request">
                                                    @csrf
                                                    <button type="submit" class="button button-primary button-sm" data-loading-text="Sending…">
                                                        Request to Join
                                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endunless

                {{-- Approved teams: public participant info only (no other rosters) --}}
                <section class="page-section">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">Participating Teams</div>
                            <h2>Approved Teams</h2>
                            <p>Teams approved to play in {{ $activeTournament->name }}.</p>
                        </div>
                    </div>

                    @if ($participatingTeams->isEmpty())
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                            </div>
                            <h3>No approved teams yet</h3>
                            <p>Teams appear here once an admin approves their roster.</p>
                        </div>
                    @else
                        <div class="grid grid-3">
                            @foreach ($participatingTeams as $participant)
                                <div class="card {{ $myTeam && $myTeam->id === $participant->id ? 'card-current' : '' }}" data-participating-team="{{ $participant->id }}">
                                    <div class="card-top">
                                        <h3 class="card-title mb-0">{{ $participant->name }}</h3>
                                        @if ($myTeam && $myTeam->id === $participant->id)
                                            <span class="badge badge-approved">Your team</span>
                                        @endif
                                    </div>
                                    <p class="card-text">
                                        {{ $participant->approved_players_count }} {{ $participant->approved_players_count === 1 ? 'player' : 'players' }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
@endsection

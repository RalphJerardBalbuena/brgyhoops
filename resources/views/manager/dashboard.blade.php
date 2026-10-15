@extends('layouts.app')

@section('title', 'Team Manager Dashboard')

@section('content')
    <x-page-header
        kicker="Team Manager Dashboard"
        icon="fa-table-columns"
        title="Team Manager"
        highlight="Dashboard"
        description="Manage your team, players, tournament schedule, and other team-related activities from this dashboard."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <!-- Active Tournament -->
            <section class="page-section">
                <div class="card card-ring card-current">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        </div>

                        <span class="badge badge-{{ $activeTournament->status }}">
                            Status: {{ ucfirst($activeTournament->status) }}
                        </span>
                    </div>

                    <h2 class="card-title">
                        {{ $activeTournament->name }}
                    </h2>

                    <p class="card-text">
                        You are currently managing this tournament.
                    </p>

                    @if ($activeTournament->description)
                        <p class="card-text" style="margin-top: 8px;">
                            {{ $activeTournament->description }}
                        </p>
                    @endif

                    <div class="card-footer">
                        <span class="card-subtitle">Active tournament</span>

                        <a href="{{ route('tournaments.index') }}" class="button button-dark button-sm">
                            <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                            Change Tournament
                        </a>
                    </div>
                </div>
            </section>

            <!-- Dashboard Overview -->
            <section class="page-section">
                <div class="grid grid-3">
                    <div class="card card-hover card-ring">
                        <div class="card-icon" style="margin-bottom: 18px;">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        </div>
                        <h3 class="card-title">My Team</h3>
                        <p class="card-text">
                            View and manage your team's information,
                            registration details, and tournament participation.
                        </p>
                    </div>

                    <div class="card card-hover card-ring">
                        <div class="card-icon" style="margin-bottom: 18px;">
                            <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                        </div>
                        <h3 class="card-title">Players</h3>
                        <p class="card-text">
                            Review and manage the players registered
                            under your team.
                        </p>
                    </div>

                    <div class="card card-hover card-ring">
                        <div class="card-icon" style="margin-bottom: 18px;">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        </div>
                        <h3 class="card-title">Schedule</h3>
                        <p class="card-text">
                            View upcoming games, dates, times,
                            and tournament venues.
                        </p>
                    </div>
                </div>
            </section>

            <section class="page-section">
                <div class="grid grid-2">
                    <!-- Team Information -->
                    <div>
                        <div class="section-heading">
                            <div>
                                <div class="section-kicker">Team Information</div>
                                <h2>My Team</h2>
                                <p>Your team's current information and registration details.</p>
                            </div>
                        </div>

                        @if ($teams->isEmpty())
                            <div class="card">
                                <p class="card-text">
                                    Team information will appear here once your team
                                    has been registered.
                                </p>

                                <div class="details">
                                    <div>
                                        <div class="detail-label">Team Manager</div>
                                        <div class="detail-value">{{ auth()->user()->name }}</div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="{{ route('teams.create') }}" class="button button-primary button-sm">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                        Create a Team
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="card">
                                <div class="details mt-0">
                                    <div>
                                        <div class="detail-label">Teams</div>
                                        <div class="detail-value">{{ $teams->count() }}</div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Team Manager</div>
                                        <div class="detail-value">{{ auth()->user()->name }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Team Status -->
                    <div>
                        <div class="section-heading">
                            <div>
                                <div class="section-kicker">Team Status</div>
                                <h2>Registration</h2>
                                <p>Current registration status of your team.</p>
                            </div>
                        </div>

                        <div class="card">
                            @forelse ($teams as $team)
                                <div class="list-row" style="padding: 10px 0; {{ $loop->first ? 'border-top: 0;' : '' }}">
                                    <span class="list-title">{{ $team->name }}</span>
                                    <x-status-badge :status="$team->application_status ?? 'draft'" />
                                </div>
                            @empty
                                <div class="detail-label">Registration Status</div>
                                <div style="margin-top: 10px;">
                                    <span class="badge badge-draft">No team yet</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <!-- Player Roster -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Player Roster</div>
                        <h2>Player Roster</h2>
                        <p>Players registered under your team.</p>
                    </div>

                    <a href="{{ route('manager.players.requests') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                        Player Requests
                    </a>
                </div>

                @forelse ($teams as $team)
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-top">
                            <h3 class="card-title mb-0">{{ $team->name }}</h3>

                            @if ($team->pending_requests_count > 0)
                                <a href="{{ route('manager.players.requests') }}" class="badge badge-pending" style="text-decoration: none;">
                                    {{ $team->pending_requests_count }} pending request(s)
                                </a>
                            @endif
                        </div>

                        @include('teams._roster', ['team' => $team])
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                        </div>
                        <h3>No players registered yet</h3>
                        <p>
                            Your team's player roster will appear here
                            once players have been added.
                        </p>
                    </div>
                @endforelse
            </section>

            <!-- Upcoming Games -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Upcoming Games</div>
                        <h2>Upcoming Games</h2>
                        <p>Your team's upcoming tournament games.</p>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Upcoming Game</h3>

                    <div class="details details-3">
                        <div>
                            <div class="detail-label">Date</div>
                            <div class="detail-value">To be announced</div>
                        </div>
                        <div>
                            <div class="detail-label">Time</div>
                            <div class="detail-value">To be announced</div>
                        </div>
                        <div>
                            <div class="detail-label">Venue</div>
                            <div class="detail-value">To be announced</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Shortcuts</div>
                        <h2>Quick Actions</h2>
                        <p>Access common team management functions.</p>
                    </div>
                </div>

                <div class="grid grid-3">
                    <a href="{{ route('teams.index') }}" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Manage Team</h3>
                            <p>Update your team information</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <div class="card action-card is-disabled" aria-disabled="true">
                        <div class="card-icon">
                            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Submit Roster</h3>
                            <p>Submit your team's player roster (not yet available)</p>
                        </div>
                        <i class="fa-solid fa-lock action-arrow" aria-hidden="true"></i>
                    </div>

                    <a href="{{ route('games.index') }}" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>View Schedule</h3>
                            <p>Check upcoming tournament games</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Recent Activity</div>
                        <h2>Recent Activity</h2>
                        <p>Recent team activities will appear here.</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    </div>
                    <h3>No recent activity</h3>
                    <p>
                        Recent team updates, roster submissions,
                        and tournament activities will be displayed here.
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection

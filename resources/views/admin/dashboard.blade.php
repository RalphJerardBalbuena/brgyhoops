@extends('layouts.app')

@section('title', 'Administrator Dashboard')

@section('content')
    <x-page-header
        kicker="Admin Control Center"
        icon="fa-gauge-high"
        title="Administrator"
        highlight="Dashboard"
        description="Welcome to BrgyHoops. Manage tournaments, track participating teams, review registered players, and access tournament information from one place."
    >
        <a href="{{ route('admin.tournaments.create') }}" class="button button-primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Create Tournament
        </a>

        <a href="/tournament" class="button button-outline">
            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            Public Dashboard
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <!-- Summary statistics -->
            <section class="grid grid-3 page-section" aria-label="Tournament statistics">
                <article class="card card-ring">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        </div>
                        <span class="card-subtitle">Overview</span>
                    </div>

                    <span class="stat-number">{{ $tournaments->count() }}</span>
                    <span class="stat-label">Tournaments</span>
                    <p class="card-text" style="margin-top: 8px;">View and manage basketball tournaments.</p>
                </article>

                <article class="card card-ring">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        </div>
                        <span class="card-subtitle">Participation</span>
                    </div>

                    <span class="stat-number">{{ $tournaments->sum('teams_count') }}</span>
                    <span class="stat-label">Teams</span>
                    <p class="card-text" style="margin-top: 8px;">Participating teams across all tournaments.</p>
                </article>

                <article class="card card-ring">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                        </div>
                        <span class="card-subtitle">Community</span>
                    </div>

                    <span class="stat-number">{{ \App\Models\User::where('role', 'player')->count() }}</span>
                    <span class="stat-label">Registered Players</span>
                    <p class="card-text" style="margin-top: 8px;">Players registered in the BrgyHoops system.</p>
                </article>
            </section>

            <!-- Tournament context -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Competition Overview</div>
                        <h2>Tournament Context</h2>
                        <p>Review tournament status, participating teams, and game counts.</p>
                    </div>

                    <a href="{{ route('admin.tournaments.index') }}" class="button button-light button-sm">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        All Tournaments
                    </a>
                </div>

                @if ($tournaments->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        </div>

                        <h3>No tournaments available</h3>

                        <p>
                            No tournaments have been created yet. Once tournaments are available,
                            they will appear here.
                        </p>
                    </div>
                @else
                    <div class="grid grid-3">
                        @foreach ($tournaments as $tournament)
                            <article class="card card-hover card-ring">
                                <div class="card-top">
                                    <div class="card-icon">
                                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                    </div>

                                    <x-status-badge :status="$tournament->status" />
                                </div>

                                <h3 class="card-title">{{ $tournament->name }}</h3>

                                <div class="details">
                                    <div>
                                        <div class="detail-label">Teams</div>
                                        <div class="detail-value">{{ $tournament->teams_count }}</div>
                                    </div>

                                    <div>
                                        <div class="detail-label">Games</div>
                                        <div class="detail-value">{{ $tournament->games_count }}</div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="{{ url('/tournament/' . $tournament->id) }}" class="card-link">
                                        View Public Dashboard
                                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <!-- Quick actions -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Shortcuts</div>
                        <h2>Quick Actions</h2>
                        <p>Access common tournament management pages.</p>
                    </div>
                </div>

                <div class="grid grid-3">
                    <a href="{{ route('admin.tournaments.index') }}" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Manage Tournaments</h3>
                            <p>View available tournament information.</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <a href="{{ route('admin.team-applications.index') }}" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Manage Teams</h3>
                            <p>Review participating teams.</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <a href="/games" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Game Schedule</h3>
                            <p>View and manage scheduled games.</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <a href="/standings" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-ranking-star" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Standings</h3>
                            <p>Review tournament standings and records.</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <a href="/tournament" class="card card-hover action-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-display" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Public Dashboard</h3>
                            <p>Open the public tournament dashboard.</p>
                        </div>
                        <i class="fa-solid fa-arrow-right action-arrow" aria-hidden="true"></i>
                    </a>

                    <div class="card action-card is-disabled" aria-label="Announcements, not yet configured">
                        <div class="card-icon">
                            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>Announcements</h3>
                            <p>Announcement management is not yet configured.</p>
                        </div>
                        <i class="fa-solid fa-lock action-arrow" aria-hidden="true"></i>
                    </div>
                </div>
            </section>

            <!-- Recent activity -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">System Overview</div>
                        <h2>Recent Activity</h2>
                        <p>A space for recent administrator and tournament activity.</p>
                    </div>
                </div>

                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                    </div>

                    <h3>No Recent Activity</h3>

                    <p>
                        Recent tournament, team, player, and system activities will be displayed
                        here when activity tracking is configured.
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection

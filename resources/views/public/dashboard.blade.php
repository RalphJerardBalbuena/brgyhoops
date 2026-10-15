@extends('layouts.app')

@section('title', $tournament ? $tournament->name : 'Public Tournament Dashboard')

@php
    // Null when the tournament has no venue yet (or the venue column is not migrated).
    $tournamentVenue = \App\Support\GoogleMaps::usableVenue($tournament?->venue);
@endphp

@section('content')
    @if ($tournament)
        <x-page-header
            kicker="Inter-Barangay Basketball Tournament Information"
            icon="fa-basketball"
            :title="$tournament->name"
            :description="$tournament->description ?: 'View tournament updates, participating teams, upcoming games, and announcements.'"
        >
            <x-slot:meta>
                <span>
                    <i class="fa-solid fa-signal" aria-hidden="true"></i>
                    Status:
                </span>
                <x-status-badge :status="$tournament->status" />
                <span>
                    <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    <strong>{{ $teams->count() }}</strong> team(s)
                </span>
                <span>
                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                    <strong>{{ $games->count() }}</strong> upcoming game(s)
                </span>
                @if ($tournamentVenue)
                    <span>
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <strong>{{ $tournamentVenue }}</strong>
                    </span>
                @endif
            </x-slot:meta>
        </x-page-header>
    @else
        <x-page-header
            kicker="Public Tournament Dashboard"
            icon="fa-basketball"
            title="BrgyHoops"
            highlight="Tournament"
            description="No active tournament is currently available."
        />
    @endif

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            <!-- Tournament Information -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Tournament Information</div>
                        <h2>At a Glance</h2>
                        <p>Public tournament information available to everyone.</p>
                    </div>
                </div>

                <div class="grid grid-4">
                    <div class="card stat-card">
                        <div class="card-icon"><i class="fa-solid fa-trophy" aria-hidden="true"></i></div>
                        <div>
                            <span class="stat-label">Tournament</span>
                            <span class="detail-value">{{ $tournament?->name ?? 'No tournament selected' }}</span>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="card-icon"><i class="fa-solid fa-signal" aria-hidden="true"></i></div>
                        <div>
                            <span class="stat-label">Status</span>
                            <span class="detail-value">
                                {{ $tournament?->status ? ucfirst($tournament->status) : 'Unavailable' }}
                            </span>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="card-icon"><i class="fa-solid fa-people-group" aria-hidden="true"></i></div>
                        <div>
                            <span class="stat-number">{{ $teams->count() }}</span>
                            <span class="stat-label">team(s) participating in this tournament.</span>
                        </div>
                    </div>

                    <div class="card stat-card">
                        <div class="card-icon"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></div>
                        <div>
                            <span class="stat-number">{{ $games->count() }}</span>
                            <span class="stat-label">upcoming game(s) scheduled for this tournament.</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Tournament primary venue (Google Maps Embed API) --}}
            @if ($tournament)
                <section class="page-section">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">Venue</div>
                            <h2>Tournament Venue</h2>
                            <p>The main venue for {{ $tournament->name }}. Individual games may be played elsewhere — see each game's venue.</p>
                        </div>
                    </div>

                    @if ($tournamentVenue)
                        <x-venue-map :venue="$tournamentVenue" />
                    @else
                        <div class="card stat-card">
                            <div class="card-icon">
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                            </div>
                            <div>
                                <span class="stat-label">Venue</span>
                                <span class="detail-value">To be announced</span>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <!-- Search and Filter -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Search</div>
                        <h2>Find Tournament Information</h2>
                        <p>Search or filter publicly available tournament information.</p>
                    </div>
                </div>

                <form class="card" action="#" method="GET">
                    <div class="search-grid">
                        <div class="field">
                            <label for="public-search" class="sr-only">Search</label>
                            <input
                                type="text"
                                id="public-search"
                                name="search"
                                placeholder="Search teams, games, or announcements"
                            >
                        </div>

                        <div class="field">
                            <label for="public-category" class="sr-only">Category</label>
                            <select id="public-category" name="category">
                                <option value="">All Categories</option>
                                <option value="teams">Teams</option>
                                <option value="games">Games</option>
                                <option value="announcements">Announcements</option>
                            </select>
                        </div>

                        <button type="submit" class="button button-primary">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            Search
                        </button>
                    </div>
                </form>
            </section>

            <!-- Participating Teams -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Participating Teams</div>
                        <h2>Teams</h2>
                        <p>
                            Approved teams participating in
                            {{ $tournament?->name ?? 'the tournament' }}.
                        </p>
                    </div>
                </div>

                @if ($teams->count())
                    <div class="grid grid-3">
                        @foreach ($teams as $team)
                            <div class="card card-hover card-ring">
                                <div class="card-top">
                                    <div class="card-icon">
                                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                    </div>
                                    <span class="badge badge-approved">Participating</span>
                                </div>

                                <h3 class="card-title">
                                    {{ $team->name }}
                                </h3>

                                <p class="card-text">
                                    {{ $team->description ?: 'Participating basketball team.' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        </div>
                        <h3>No Teams Available</h3>
                        <p>No participating teams have been added to this tournament yet.</p>
                    </div>
                @endif
            </section>

            <!-- Upcoming Games -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Upcoming Games</div>
                        <h2>Game Schedule</h2>
                        <p>
                            View publicly available upcoming games for
                            {{ $tournament?->name ?? 'the tournament' }}.
                        </p>
                    </div>
                </div>

                @if ($games->count())
                    <div class="grid grid-2">
                        @foreach ($games as $game)
                            <div class="card card-hover">
                                <div class="card-top">
                                    <h3 class="card-title mb-0">
                                        {{ $game->homeTeam?->name ?? 'Home Team' }}
                                        <span class="text-red">vs</span>
                                        {{ $game->awayTeam?->name ?? 'Away Team' }}
                                    </h3>

                                    <x-status-badge :status="$game->status" />
                                </div>

                                <div class="details details-3 mt-0">
                                    <div>
                                        <div class="detail-label">Date</div>
                                        <div class="detail-value">
                                            {{ $game->scheduled_at ? $game->scheduled_at->format('F d, Y') : 'To be announced' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Time</div>
                                        <div class="detail-value">
                                            {{ $game->scheduled_at ? $game->scheduled_at->format('h:i A') : 'To be announced' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="detail-label">Venue</div>
                                        <div class="detail-value">{{ $game->venue ?? 'To be announced' }}</div>
                                    </div>
                                </div>

                                @if ($game->hasCoordinates())
                                    {{-- Exact pin: compact map + directions to the coordinates --}}
                                    <x-game-location-map :game="$game" />
                                @elseif ($gameDirections = \App\Support\GoogleMaps::directionsUrl($game->venue))
                                    <div class="card-footer">
                                        <a href="{{ $gameDirections }}" class="card-link" target="_blank" rel="noopener noreferrer">
                                            <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
                                            Directions to venue
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                        </div>
                        <h3>No Upcoming Games</h3>
                        <p>No upcoming games have been scheduled for this tournament yet.</p>
                    </div>
                @endif
            </section>

            {{-- Game venues (Google Maps Embed API) — distinct usable venues of upcoming
                 games that have no exact pin (pinned games already show their own map) --}}
            @php
                $gameVenues = $games
                    ->reject(fn ($game) => $game->hasCoordinates())
                    ->map(fn ($game) => \App\Support\GoogleMaps::usableVenue($game->venue))
                    ->filter()
                    ->unique(fn ($venue) => strtolower($venue))
                    // Already mapped in the Tournament Venue section above.
                    ->reject(fn ($venue) => $tournamentVenue && strtolower($venue) === strtolower($tournamentVenue))
                    ->values();
            @endphp

            @if ($gameVenues->isNotEmpty())
                <section class="page-section">
                    <div class="section-heading">
                        <div>
                            <div class="section-kicker">Venues</div>
                            <h2>Game Venues</h2>
                            <p>Where upcoming games in {{ $tournament?->name ?? 'the tournament' }} will be played.</p>
                        </div>
                    </div>

                    <div class="grid grid-2">
                        @foreach ($gameVenues as $venue)
                            <x-venue-map :venue="$venue" />
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Announcements -->
            <section class="page-section">
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Announcements</div>
                        <h2>Announcements</h2>
                        <p>Important tournament updates and public announcements.</p>
                    </div>
                </div>

                <div class="card card-ring">
                    <div class="card-top">
                        <div class="card-icon">
                            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                        </div>
                        <span class="card-subtitle">{{ $tournament?->name ?? 'Tournament Update' }}</span>
                    </div>

                    <h3 class="card-title">Tournament Announcement</h3>

                    <p class="card-text">
                        Tournament announcements and important updates
                        will appear here.
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection

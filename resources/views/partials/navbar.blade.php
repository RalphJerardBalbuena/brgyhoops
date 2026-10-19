@php
    $navUser = auth()->user();
    $navRole = $navUser?->role;

    $roleLabels = [
        'admin' => 'Administrator',
        'team_manager' => 'Team Manager',
        'player' => 'Player',
    ];

    // [label, icon, url, active route patterns]
    $navItems = match ($navRole) {
        'admin' => [
            ['Dashboard', 'fa-gauge-high', route('admin.dashboard'), ['admin.dashboard']],
            ['Tournaments', 'fa-trophy', route('admin.tournaments.index'), ['admin.tournaments.*', 'tournaments.*']],
            ['Applications', 'fa-clipboard-check', route('admin.team-applications.index'), ['admin.team-applications.*']],
            ['Manager Applications', 'fa-file-signature', route('admin.tournament-applications.index'), ['admin.tournament-applications.*']],
            ['Schedule', 'fa-calendar-days', route('games.index'), ['games.*']],
            ['Standings', 'fa-ranking-star', route('standings.index'), ['standings.*']],
        ],
        'team_manager' => [
            ['Dashboard', 'fa-table-columns', route('manager.dashboard'), ['manager.dashboard']],
            ['Tournaments', 'fa-trophy', route('tournaments.index'), ['tournaments.*']],
            ['Open Tournaments', 'fa-door-open', route('manager.open-tournaments.index'), ['manager.open-tournaments.*']],
            ['Teams', 'fa-people-group', route('teams.index'), ['teams.*']],
            ['Schedule', 'fa-calendar-days', route('games.index'), ['games.*']],
            ['Standings', 'fa-ranking-star', route('standings.index'), ['standings.*']],
            ['Requests', 'fa-user-check', route('manager.players.requests'), ['manager.players.*']],
        ],
        'player' => [
            ['My Team', 'fa-people-group', route('player.teams.index'), ['player.teams.*']],
            ['Schedule', 'fa-calendar-days', route('player.schedule'), ['player.schedule']],
            ['Public Site', 'fa-display', route('public.dashboard'), ['public.*']],
        ],
        default => [
            ['Home', 'fa-house', route('home'), ['home']],
            ['Tournaments', 'fa-trophy', route('public.dashboard'), ['public.*']],
            ['Login', 'fa-right-to-bracket', route('login'), ['login', 'password.request']],
        ],
    };
@endphp

<nav class="navbar">
    <div class="container nav-inner">

        <a href="/" class="brand" aria-label="BrgyHoops home">
            <span class="brand-icon">
                <i class="fa-solid fa-basketball" aria-hidden="true"></i>
            </span>

            <span class="brand-text">
                <span class="brand-name">Brgy<span>Hoops</span></span>
                @if ($navRole && isset($roleLabels[$navRole]))
                    <span class="brand-role">{{ $roleLabels[$navRole] }}</span>
                @endif
            </span>
        </a>

        <div class="nav-links">
            @foreach ($navItems as [$label, $icon, $url, $patterns])
                <a href="{{ $url }}" @if (request()->routeIs(...$patterns)) aria-current="page" @endif>
                    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                    {{ $label }}
                </a>
            @endforeach

            @if ($navUser)
                <form class="nav-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-button nav-logout" data-loading-text="Signing out…">
                        <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        Logout
                    </button>
                </form>

                <span class="nav-user" title="{{ $navUser->email }}">
                    <span class="nav-avatar">{{ \Illuminate\Support\Str::substr($navUser->name ?? 'U', 0, 1) }}</span>
                    {{ \Illuminate\Support\Str::limit($navUser->name, 18) }}
                </span>
            @else
                <a href="{{ route('register') }}" class="nav-register">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Register
                </a>
            @endif
        </div>

    </div>
</nav>

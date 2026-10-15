@php
    $footerRole = auth()->user()?->role;
@endphp

<footer class="footer">
    <div class="container">

        <div class="footer-main">
            <div>
                <a href="/" class="footer-brand">Brgy<span>Hoops</span></a>

                <p class="footer-description">
                    Inter-Barangay Basketball Tournament Management System.
                    Bringing communities together, one game at a time.
                </p>
            </div>

            <div class="footer-links">
                <a href="/">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    Home
                </a>

                <a href="{{ route('public.dashboard') }}">
                    <i class="fa-solid fa-display" aria-hidden="true"></i>
                    Public Dashboard
                </a>

                @if ($footerRole === 'admin')
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                        Admin Dashboard
                    </a>
                @elseif ($footerRole === 'team_manager')
                    <a href="{{ route('manager.dashboard') }}">
                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
                        Manager Dashboard
                    </a>
                @elseif ($footerRole === 'player')
                    <a href="{{ route('player.teams.index') }}">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        Find Teams
                    </a>
                @else
                    <a href="{{ route('login') }}">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        Login
                    </a>

                    <a href="{{ route('register') }}">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        Register
                    </a>
                @endif
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} BrgyHoops. All rights reserved.</p>

            <p>
                Made for the love of the game
                <i class="fa-solid fa-basketball" aria-hidden="true"></i>
            </p>
        </div>

    </div>
</footer>

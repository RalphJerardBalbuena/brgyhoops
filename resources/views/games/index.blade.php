<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Schedule</title>
    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>
<body>
    <h1>Game Schedule</h1>

    <p>
        <strong>Active Tournament:</strong>
        {{ $activeTournament->name }}
    </p>

    <p>
        <a href="{{ route('manager.dashboard') }}">Back to Dashboard</a> |
        <a href="{{ route('teams.index') }}">Manage Teams</a> |
        <a href="{{ route('games.create') }}">Schedule New Game</a> |
        <a href="{{ route('standings.index') }}">View Standings</a>
    </p>

    @if (session('success'))
        <p>
            {{ session('success') }}
        </p>
    @endif

    @if ($games->isEmpty())
        <p>No games have been scheduled for this tournament yet.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Home Team</th>
                    <th>Away Team</th>
                    <th>Score</th>
                    <th>Venue</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($games as $game)
                    <tr>
                        <td>
                            {{ $game->scheduled_at->format('M d, Y h:i A') }}
                        </td>

                        <td>
                            {{ $game->homeTeam->name }}
                        </td>

                        <td>
                            {{ $game->awayTeam->name }}
                        </td>

                        <td>
                            @if ($game->home_score !== null && $game->away_score !== null)
                                {{ $game->home_score }} - {{ $game->away_score }}
                            @else
                                Not played
                            @endif
                        </td>

                        <td>
                            {{ $game->venue ?: 'TBA' }}
                        </td>

                        <td>
                            {{ ucfirst($game->status) }}
                        </td>

                        <td>
                            <a href="{{ route('games.edit', $game) }}">
                                Edit
                            </a>

                            <form
                                action="{{ route('games.destroy', $game) }}"
                                method="POST"
                                style="display:inline;"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm('Delete this scheduled game?')"
                                >
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
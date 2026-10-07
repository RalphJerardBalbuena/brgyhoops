<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Standings</title>
    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>
<body>
    <h1>Tournament Standings</h1>

    <p>
        <strong>Active Tournament:</strong>
        {{ $activeTournament->name }}
    </p>

    <p>
        <a href="{{ route('manager.dashboard') }}">Back to Dashboard</a> |
        <a href="{{ route('games.index') }}">Game Schedule</a> |
        <a href="{{ route('teams.index') }}">Manage Teams</a>
    </p>

    @if (empty($standings))
        <p>No teams are registered in this tournament yet.</p>
    @else
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Team</th>
                    <th>Played</th>
                    <th>Wins</th>
                    <th>Losses</th>
                    <th>Points</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($standings as $index => $standing)
                    <tr>
                        <td>
                            {{ $index + 1 }}
                        </td>

                        <td>
                            {{ $standing['team']->name }}
                        </td>

                        <td>
                            {{ $standing['played'] }}
                        </td>

                        <td>
                            {{ $standing['wins'] }}
                        </td>

                        <td>
                            {{ $standing['losses'] }}
                        </td>

                        <td>
                            {{ $standing['points'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
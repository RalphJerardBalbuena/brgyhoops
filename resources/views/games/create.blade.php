<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule New Game</title>
    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>
<body>
    <h1>Schedule New Game</h1>

    <p>
        <strong>Active Tournament:</strong>
        {{ $activeTournament->name }}
    </p>

    <p>
        <a href="{{ route('games.index') }}">Back to Schedule</a>
    </p>

    @if ($errors->any())
        <div>
            <strong>Please fix the following errors:</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('games.store') }}" method="POST">
        @csrf

        <div>
            <label for="home_team_id">Home Team:</label>

            <select name="home_team_id" id="home_team_id" required>
                <option value="">Select Home Team</option>

                @foreach ($teams as $team)
                    <option
                        value="{{ $team->id }}"
                        {{ old('home_team_id') == $team->id ? 'selected' : '' }}
                    >
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="away_team_id">Away Team:</label>

            <select name="away_team_id" id="away_team_id" required>
                <option value="">Select Away Team</option>

                @foreach ($teams as $team)
                    <option
                        value="{{ $team->id }}"
                        {{ old('away_team_id') == $team->id ? 'selected' : '' }}
                    >
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="scheduled_at">Date & Time:</label>

            <input
                type="datetime-local"
                name="scheduled_at"
                id="scheduled_at"
                value="{{ old('scheduled_at') }}"
                required
            >
        </div>

        <br>

        <div>
            <label for="home_score">Home Score:</label>

            <input
                type="number"
                name="home_score"
                id="home_score"
                value="{{ old('home_score') }}"
                min="0"
                placeholder="Leave empty if not completed"
            >
        </div>

        <br>

        <div>
            <label for="away_score">Away Score:</label>

            <input
                type="number"
                name="away_score"
                id="away_score"
                value="{{ old('away_score') }}"
                min="0"
                placeholder="Leave empty if not completed"
            >
        </div>

        <br>

        <div>
            <label for="venue">Venue:</label>

            <input
                type="text"
                name="venue"
                id="venue"
                value="{{ old('venue') }}"
                maxlength="255"
                placeholder="Enter venue"
            >
        </div>

        <br>

        <div>
            <label for="status">Status:</label>

            <select name="status" id="status" required>
                <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>
                    Scheduled
                </option>

                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>
                    Completed
                </option>

                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>
                    Cancelled
                </option>
            </select>
        </div>

        <br>

        <button type="submit">
            Schedule Game
        </button>
    </form>
</body>
</html>
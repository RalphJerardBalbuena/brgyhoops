<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Teams - BrgyHoops</title>

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>

<body>

    <main class="container">
        <section>

            <h1>My Teams</h1>

            <p>
                Manage your teams for the currently selected tournament.
            </p>

            <div>
                <strong>Active Tournament:</strong>
                {{ $activeTournament->name }}
            </div>

            @if (session('success'))
                <p>
                    {{ session('success') }}
                </p>
            @endif

            <p>
                <a href="{{ route('teams.create') }}">
                    Create New Team
                </a>
            </p>

            @if ($teams->isEmpty())
                <p>
                    You do not have any teams registered for this tournament yet.
                </p>
            @else
                @foreach ($teams as $team)
                    <article>
                        <h2>{{ $team->name }}</h2>

                        @if ($team->short_name)
                            <p>
                                Short Name: {{ $team->short_name }}
                            </p>
                        @endif

                        @if ($team->description)
                            <p>
                                {{ $team->description }}
                            </p>
                        @endif

                        <p>
                            <a href="{{ route('teams.edit', $team) }}">
                                Edit Team
                            </a>
                        </p>

                        <form
                            method="POST"
                            action="{{ route('teams.destroy', $team) }}"
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit">
                                Delete Team
                            </button>
                        </form>
                    </article>
                @endforeach
            @endif

            <p>
                <a href="{{ route('manager.dashboard') }}">
                    Back to Dashboard
                </a>
            </p>

        </section>
    </main>

</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Select Tournament - BrgyHoops</title>

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>

<body>

    <main class="container">
        <section>
            <h1>Select Tournament</h1>

            <p>
                Choose the tournament you want to manage.
            </p>

            @if ($tournaments->isEmpty())
                <p>No tournaments are currently assigned to your account.</p>
            @else
                <div>
                    @foreach ($tournaments as $tournament)
                        <div>
                            <h2>{{ $tournament->name }}</h2>

                            @if ($tournament->description)
                                <p>{{ $tournament->description }}</p>
                            @endif

                            <p>
                                Status: {{ ucfirst($tournament->status) }}
                            </p>

                            <form method="POST" action="{{ route('tournaments.select', $tournament) }}">
                                @csrf

                                <button type="submit">
                                    Select Tournament
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <p>
                <a href="{{ url('/manager') }}">Back to Dashboard</a>
            </p>
        </section>
    </main>

</body>
</html>
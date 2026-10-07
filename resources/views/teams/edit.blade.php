<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Team - BrgyHoops</title>

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
</head>

<body>

    <main class="container">
        <section>

            <h1>Edit Team</h1>

            <p>
                Update your team's information.
            </p>

            <p>
                <strong>Active Tournament:</strong>
                {{ $activeTournament->name }}
            </p>

            @if ($errors->any())
                <div>
                    <strong>Please correct the following errors:</strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('teams.update', $team) }}"
            >
                @csrf
                @method('PUT')

                <div>
                    <label for="name">Team Name</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $team->name) }}"
                        required
                    >
                </div>

                <div>
                    <label for="short_name">Short Name</label>

                    <input
                        type="text"
                        id="short_name"
                        name="short_name"
                        value="{{ old('short_name', $team->short_name) }}"
                        maxlength="20"
                    >
                </div>

                <div>
                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                    >{{ old('description', $team->description) }}</textarea>
                </div>

                <button type="submit">
                    Save Changes
                </button>
            </form>

            <p>
                <a href="{{ route('teams.index') }}">
                    Cancel
                </a>
            </p>

        </section>
    </main>

</body>
</html>
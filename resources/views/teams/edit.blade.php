@extends('layouts.app')

@section('title', 'Edit Team')

@section('content')
    <x-page-header
        kicker="Team Management"
        icon="fa-pen-to-square"
        title="Edit"
        highlight="Team"
        description="Update your team's information."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form method="POST" action="{{ route('teams.update', $team) }}" class="card form-card">
                @csrf
                @method('PUT')

                @include('teams._form', ['team' => $team])

                <div class="form-actions">
                    <a href="{{ route('teams.index') }}" class="button button-light">
                        Cancel
                    </a>

                    <button type="submit" class="button button-primary" data-loading-text="Saving…">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

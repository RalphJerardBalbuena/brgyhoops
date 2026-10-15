@extends('layouts.app')

@section('title', 'Create Team')

@section('content')
    <x-page-header
        kicker="Team Management"
        icon="fa-people-group"
        title="Create"
        highlight="Team"
        description="Create a team for the currently selected tournament."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form method="POST" action="{{ route('teams.store') }}" class="card form-card">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ $activeTournament->id }}">

                @include('teams._form', ['team' => null])

                <div class="form-actions">
                    <a href="{{ route('teams.index') }}" class="button button-light">
                        Cancel
                    </a>

                    <button type="submit" class="button button-primary" data-loading-text="Creating team…">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Create Team
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

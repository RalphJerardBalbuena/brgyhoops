@extends('layouts.app')

@section('title', 'Schedule New Game')

@section('content')
    <x-page-header
        kicker="Game Schedule"
        icon="fa-calendar-plus"
        title="Schedule New Game"
        description="Add a matchup between two teams in the selected tournament."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form action="{{ route('games.store') }}" method="POST" class="card form-card">
                @csrf
                <input type="hidden" name="tournament_id" value="{{ $activeTournament->id }}">

                @include('games._form', ['game' => null])

                <div class="form-actions">
                    <a href="{{ route('games.index') }}" class="button button-light">
                        Back to Schedule
                    </a>

                    <button type="submit" class="button button-primary" data-loading-text="Scheduling…">
                        <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i>
                        Schedule Game
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

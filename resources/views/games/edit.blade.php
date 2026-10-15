@extends('layouts.app')

@section('title', 'Edit Scheduled Game')

@section('content')
    <x-page-header
        kicker="Game Schedule"
        icon="fa-pen-to-square"
        title="Edit Scheduled Game"
        description="Update the matchup, schedule, venue, status, or final score."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form action="{{ route('games.update', $game) }}" method="POST" class="card form-card">
                @csrf
                @method('PUT')

                @include('games._form', ['game' => $game])

                <div class="form-actions">
                    <a href="{{ route('games.index') }}" class="button button-light">
                        Back to Schedule
                    </a>

                    <button type="submit" class="button button-primary" data-loading-text="Saving…">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Update Game
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

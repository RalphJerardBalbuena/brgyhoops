@extends('layouts.app')

@section('title', 'Edit Tournament')

@section('content')
    <x-page-header
        kicker="Tournament Management"
        icon="fa-pen-to-square"
        title="Edit"
        highlight="Tournament"
        :description="'Update the details of ' . $tournament->name . '. Teams, games, and manager assignments are not changed.'"
    >
        <a href="{{ route('admin.tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form method="POST" action="{{ route('admin.tournaments.update', $tournament) }}" class="card form-card">
                @csrf
                @method('PUT')

                @include('admin.tournaments._fields', ['tournament' => $tournament])

                <h2 class="form-section-title">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    Assigned team managers
                </h2>

                <div class="details">
                    <div>
                        <div class="detail-label">Managers</div>
                        <div class="detail-value">
                            {{ $tournament->users->pluck('name')->join(', ') ?: 'No managers assigned yet' }}
                        </div>
                    </div>

                    <div>
                        <div class="detail-label">Teams / Games</div>
                        <div class="detail-value">{{ $tournament->teams_count }} / {{ $tournament->games_count }}</div>
                    </div>
                </div>

                <p class="field-hint" style="margin-top: 10px;">
                    Saving this form keeps the current manager assignments, teams, and games exactly as they are.
                </p>

                <div class="form-actions">
                    <a href="{{ route('admin.tournaments.index') }}" class="button button-light">
                        Cancel
                    </a>
                    <button type="submit" class="button button-primary" data-loading-text="Saving…">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

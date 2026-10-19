@extends('layouts.app')

@section('title', 'Edit Tournament')

@section('content')
    <x-page-header
        kicker="Tournament Management"
        icon="fa-pen-to-square"
        title="Edit"
        highlight="Tournament"
        :description="'Update the details of ' . $tournament->name . '. Teams, games, and manager participation are not changed.'"
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

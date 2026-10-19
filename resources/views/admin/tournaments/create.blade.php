@extends('layouts.app')

@section('title', 'Create a Tournament')

@section('content')
    <x-page-header
        kicker="Tournament Management"
        icon="fa-trophy"
        title="Create a"
        highlight="Tournament"
        description="Set up a competition. While it is upcoming, team managers can apply to take part, and you approve or reject each application."
    >
        <a href="{{ route('admin.tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form method="POST" action="{{ route('admin.tournaments.store') }}" class="card form-card">
                @csrf

                @include('admin.tournaments._fields', ['tournament' => null])

                <div class="form-actions">
                    <a href="{{ route('admin.tournaments.index') }}" class="button button-light">
                        Cancel
                    </a>
                    <button type="submit" class="button button-primary" data-loading-text="Creating tournament…">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Create tournament
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

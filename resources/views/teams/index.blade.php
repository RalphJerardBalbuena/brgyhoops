@extends('layouts.app')

@section('title', 'My Teams')

@section('content')
    <x-page-header
        kicker="Team Management"
        icon="fa-people-group"
        title="My"
        highlight="Teams"
        description="Manage your teams for the currently selected tournament."
    >
        <x-slot:meta>
            <x-tournament-context :tournament="$activeTournament" />
        </x-slot:meta>

        <a href="{{ route('teams.create') }}" class="button button-primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Create New Team
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            @if ($teams->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    </div>
                    <h3>No teams yet</h3>
                    <p>You do not have any teams registered for this tournament yet.</p>

                    <a href="{{ route('teams.create') }}" class="button button-primary">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Create New Team
                    </a>
                </div>
            @else
                <div class="grid grid-2">
                    @foreach ($teams as $team)
                        <article class="card card-hover card-ring">
                            <div class="card-top">
                                <div class="card-icon">
                                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                </div>

                                @if ($team->application_status)
                                    <x-status-badge :status="$team->application_status" />
                                @endif
                            </div>

                            <h2 class="card-title">{{ $team->name }}</h2>

                            @if ($team->short_name)
                                <div class="card-subtitle">
                                    Short Name: {{ $team->short_name }}
                                </div>
                            @endif

                            @if ($team->description)
                                <p class="card-text" style="margin-top: 12px;">
                                    {{ $team->description }}
                                </p>
                            @endif

                            @include('teams._roster', ['team' => $team])

                            <div class="card-footer">
                                <a href="{{ route('teams.edit', $team) }}" class="button button-light button-sm">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                    Edit Team
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('teams.destroy', $team) }}"
                                    class="inline-form"
                                    data-confirm="Delete {{ $team->name }}? Its roster, join requests, and scheduled games will also be removed. This cannot be undone."
                                    data-confirm-title="Delete this team?"
                                    data-confirm-action="Delete team"
                                    data-confirm-variant="danger"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="button button-danger button-sm"
                                        data-loading-text="Deleting…"
                                    >
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        Delete Team
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="button-row" style="margin-top: 32px;">
                <a href="{{ route('manager.dashboard') }}" class="button button-light button-sm">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection

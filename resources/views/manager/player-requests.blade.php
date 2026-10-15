@extends('layouts.app')

@section('title', 'Player Requests')

@php
    $totalPendingRequests = $teams->sum(function ($team) {
        return $team->players->count();
    });
@endphp

@section('content')
    <x-page-header
        kicker="Team Management"
        icon="fa-user-check"
        title="Player"
        highlight="Requests"
        description="Review players who want to join your teams. Approve the players you want on your roster or reject requests that don't fit."
    >
        <x-slot:meta>
            <span>
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                Pending requests: <strong>{{ $totalPendingRequests }}</strong>
            </span>
        </x-slot:meta>

        <a href="{{ route('manager.dashboard') }}" class="button button-outline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Dashboard
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            @include('partials.flash')

            @if ($teams->isEmpty())
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    </div>
                    <h3>No teams found yet</h3>
                    <p>You don't have any teams to manage. Create a team first, and player requests will appear here when players apply to join.</p>
                    <a href="{{ route('teams.index') }}" class="button button-primary">
                        Manage Teams
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            @elseif ($totalPendingRequests === 0)
                <div class="empty-state">
                    <div class="empty-icon" style="background: var(--green-soft); color: var(--green);">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                    </div>
                    <h3>You're all caught up!</h3>
                    <p>There are no pending player requests right now. New requests will show up here when players ask to join your teams.</p>
                    <a href="{{ route('manager.dashboard') }}" class="button button-primary">
                        Back to Dashboard
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            @else
                <div class="section-heading">
                    <div>
                        <div class="section-kicker">Your Teams</div>
                        <h2>Pending Requests</h2>
                    </div>

                    <span class="section-side-note">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                        {{ $teams->count() }} {{ $teams->count() === 1 ? 'team' : 'teams' }}
                    </span>
                </div>

                <div class="stack">
                    @foreach ($teams as $team)
                        <section class="card card-flush">
                            <div class="card-panel-header">
                                <div class="list-row-main">
                                    <div class="card-icon">
                                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h3>{{ $team->name }}</h3>
                                        <p>{{ $team->tournament->name ?? 'Tournament not assigned' }}</p>
                                    </div>
                                </div>

                                <span class="badge badge-pending">
                                    {{ $team->players->count() }} Pending
                                </span>
                            </div>

                            @if ($team->players->isEmpty())
                                <div class="card-panel-body card-text">
                                    <i class="fa-solid fa-circle-check" style="color: var(--green);" aria-hidden="true"></i>
                                    No pending requests for this team.
                                </div>
                            @else
                                <div class="list">
                                    @foreach ($team->players as $player)
                                        <div class="list-row" @if ($loop->first) style="border-top: 0;" @endif>
                                            <div class="list-row-main">
                                                <div class="avatar">
                                                    {{ \Illuminate\Support\Str::substr($player->name ?? 'P', 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="list-title">{{ $player->name }}</div>
                                                    <div class="list-caption">Requested to join this team</div>
                                                </div>
                                            </div>

                                            <div class="button-row">
                                                <form method="POST" action="{{ route('manager.players.approve', ['team' => $team->id, 'playerId' => $player->id]) }}" class="inline-form" data-confirm="Add {{ $player->name }} to the {{ $team->name }} roster?" data-confirm-title="Approve this player?" data-confirm-action="Approve player">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="button button-success button-sm" data-loading-text="Approving…">
                                                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('manager.players.reject', ['team' => $team->id, 'playerId' => $player->id]) }}" class="inline-form" data-confirm="Reject {{ $player->name }}'s request to join {{ $team->name }}? They will be notified by email." data-confirm-title="Reject this request?" data-confirm-action="Reject request" data-confirm-variant="danger">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="button button-danger button-sm" data-loading-text="Rejecting…">
                                                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

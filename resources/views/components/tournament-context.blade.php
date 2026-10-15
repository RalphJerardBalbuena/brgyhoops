@props(['tournament'])

{{-- Active tournament line shown in the page header of tournament-scoped pages. --}}
<span>
    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
    Active tournament: <strong>{{ $tournament->name }}</strong>
</span>

<x-status-badge :status="$tournament->status" />

<a href="{{ route('tournaments.index') }}">
    <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
    Change Tournament
</a>

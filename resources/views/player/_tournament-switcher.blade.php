{{--
    Player tournament selector (upcoming/ongoing tournaments, plus completed
    ones the player played in — read-only).
    Expects: $tournaments, $activeTournament; optional $redirect ('schedule').
--}}
<div class="card">
    @if ($tournaments->isEmpty())
        <p class="card-text mb-0">No tournaments are open right now. Check back when the next tournament is announced.</p>
    @else
        <form method="POST" action="{{ route('player.tournaments.select') }}" class="search-grid" style="grid-template-columns: minmax(0, 1fr) auto;">
            @csrf
            <input type="hidden" name="redirect" value="{{ $redirect ?? 'teams' }}">

            <div class="field">
                <label for="player-tournament">
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    Tournament
                </label>

                <select id="player-tournament" name="tournament_id">
                    @foreach ($tournaments as $tournament)
                        <option value="{{ $tournament->id }}" @selected($activeTournament?->id === $tournament->id)>
                            {{ $tournament->name }} ({{ ucfirst($tournament->status) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="button button-dark" data-loading-text="Switching…">
                <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                Switch
            </button>
        </form>

        @if ($activeTournament && \App\Support\PlayerTournaments::isReadOnly($activeTournament))
            <p class="field-hint" style="margin-top: 10px;">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                {{ $activeTournament->name }} is {{ $activeTournament->status }} — shown read-only for your history.
            </p>
        @endif
    @endif
</div>

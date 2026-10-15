{{--
    Shared game form fields.
    Expects: $teams, and optionally $game (edit) — null when creating.
--}}
@php
    $game = $game ?? null;
    $currentStatus = old('status', $game?->status ?? 'scheduled');
@endphp

<h2 class="form-section-title">
    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
    Matchup
</h2>

@if ($teams->count() < 2)
    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <div>
            Only teams approved by an admin can be scheduled, and this tournament has
            {{ $teams->count() }} available. Review submitted teams under
            <a href="{{ route('admin.team-applications.index') }}">Team Applications</a>.
        </div>
    </div>
@endif

<div class="form-grid">
    <div class="field @error('home_team_id') has-error @enderror">
        <label for="home_team_id"><i class="fa-solid fa-house" aria-hidden="true"></i> Home Team</label>

        <select name="home_team_id" id="home_team_id" required>
            <option value="">Select Home Team</option>

            @foreach ($teams as $team)
                <option
                    value="{{ $team->id }}"
                    {{ old('home_team_id', $game?->home_team_id) == $team->id ? 'selected' : '' }}
                >
                    {{ $team->name }}
                </option>
            @endforeach
        </select>

        @error('home_team_id')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field @error('away_team_id') has-error @enderror">
        <label for="away_team_id"><i class="fa-solid fa-plane" aria-hidden="true"></i> Away Team</label>

        <select name="away_team_id" id="away_team_id" required>
            <option value="">Select Away Team</option>

            @foreach ($teams as $team)
                <option
                    value="{{ $team->id }}"
                    {{ old('away_team_id', $game?->away_team_id) == $team->id ? 'selected' : '' }}
                >
                    {{ $team->name }}
                </option>
            @endforeach
        </select>

        @error('away_team_id')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>
</div>

<h2 class="form-section-title">
    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
    Schedule & Result
</h2>

<div class="form-grid">
    <div class="field @error('scheduled_at') has-error @enderror">
        <label for="scheduled_at">Date & Time</label>

        <input
            type="datetime-local"
            name="scheduled_at"
            id="scheduled_at"
            value="{{ old('scheduled_at', $game?->scheduled_at?->format('Y-m-d\TH:i')) }}"
            required
        >

        @error('scheduled_at')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field @error('home_score') has-error @enderror">
        <label for="home_score">Home Score</label>

        <input
            type="number"
            name="home_score"
            id="home_score"
            value="{{ old('home_score', $game?->home_score) }}"
            min="0"
            placeholder="Leave empty if not completed"
        >

        @error('home_score')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field @error('away_score') has-error @enderror">
        <label for="away_score">Away Score</label>

        <input
            type="number"
            name="away_score"
            id="away_score"
            value="{{ old('away_score', $game?->away_score) }}"
            min="0"
            placeholder="Leave empty if not completed"
        >

        @error('away_score')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field @error('status') has-error @enderror">
        <label for="status">Status</label>

        <select name="status" id="status" required>
            <option value="scheduled" {{ $currentStatus === 'scheduled' ? 'selected' : '' }}>
                Scheduled
            </option>

            <option value="completed" {{ $currentStatus === 'completed' ? 'selected' : '' }}>
                Completed
            </option>

            <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>
                Cancelled
            </option>
        </select>

        @error('status')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>
</div>

<h2 class="form-section-title">
    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
    Venue & Location
</h2>

<div class="form-grid">
    <div class="field field-full @error('venue') has-error @enderror">
        <label for="venue">Venue <span class="optional">(optional)</span></label>

        <input
            type="text"
            name="venue"
            id="venue"
            value="{{ old('venue', $game?->venue) }}"
            maxlength="255"
            placeholder="Enter venue"
        >

        @error('venue')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    @if ($coordinatesSupported ?? false)
        <x-leaflet-assets />

        <div class="field field-full">
            <span class="field-label" id="venue-pin-label">
                Exact location <span class="optional">(optional)</span>
            </span>

            <div
                class="location-picker"
                data-location-picker
                data-lat-input="latitude"
                data-lng-input="longitude"
            >
                <div class="location-map" data-map-canvas role="application" aria-labelledby="venue-pin-label">
                    <p class="map-fallback">
                        Loading map… If it does not appear, type the latitude and longitude below.
                    </p>
                </div>

                <div class="location-picker-bar">
                    <span class="field-hint" data-map-status aria-live="polite">
                        Click the map to drop a pin, drag the pin to adjust it, or type exact coordinates.
                    </span>

                    <button type="button" class="button button-light button-sm" data-clear-pin hidden>
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Remove pin
                    </button>
                </div>
            </div>

            <div class="field-hint">
                Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors.
            </div>
        </div>

        <div class="field @error('latitude') has-error @enderror">
            <label for="latitude">Latitude <span class="optional">(-90 to 90)</span></label>

            <input
                type="number"
                name="latitude"
                id="latitude"
                value="{{ old('latitude', $game?->latitude !== null ? \App\Support\GoogleMaps::formatCoordinate($game->latitude) : null) }}"
                step="any"
                min="-90"
                max="90"
                inputmode="decimal"
                placeholder="e.g. 14.5995"
            >

            @error('latitude')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field @error('longitude') has-error @enderror">
            <label for="longitude">Longitude <span class="optional">(-180 to 180)</span></label>

            <input
                type="number"
                name="longitude"
                id="longitude"
                value="{{ old('longitude', $game?->longitude !== null ? \App\Support\GoogleMaps::formatCoordinate($game->longitude) : null) }}"
                step="any"
                min="-180"
                max="180"
                inputmode="decimal"
                placeholder="e.g. 120.9842"
            >

            @error('longitude')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>
    @endif
</div>

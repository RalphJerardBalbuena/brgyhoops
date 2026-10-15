@props(['game'])

{{--
    Compact map for a game with an exact pin (Leaflet + OpenStreetMap, no API key),
    plus a Google Maps directions link to the exact coordinates.
    Renders nothing when the game has no valid coordinates.
--}}
@if ($game->hasCoordinates())
    @php
        $lat = \App\Support\GoogleMaps::formatCoordinate($game->latitude);
        $lng = \App\Support\GoogleMaps::formatCoordinate($game->longitude);
        $label = \App\Support\GoogleMaps::usableVenue($game->venue) ?? 'Game venue';
    @endphp

    <x-leaflet-assets />

    <div
        class="game-map"
        data-game-map
        data-lat="{{ $lat }}"
        data-lng="{{ $lng }}"
        data-label="{{ $label }}"
        role="img"
        aria-label="Map showing the exact location of {{ $label }}"
    >
        <p class="map-fallback">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            Pinned location: {{ $lat }}, {{ $lng }}
        </p>
    </div>

    <div class="card-footer">
        <a href="{{ $game->coordinateDirectionsUrl() }}" class="card-link" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
            Directions to exact location
        </a>
        <span class="list-caption">Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> contributors</span>
    </div>
@endif

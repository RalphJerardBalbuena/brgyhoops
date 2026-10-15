@props(['venue' => null])

@php
    $usableVenue = \App\Support\GoogleMaps::usableVenue($venue);
    $embedUrl = \App\Support\GoogleMaps::embedUrl($venue);
    $directionsUrl = \App\Support\GoogleMaps::directionsUrl($venue);
@endphp

@if ($usableVenue)
    <div {{ $attributes->merge(['class' => 'card card-flush venue-map']) }}>
        @if ($embedUrl)
            <iframe
                class="venue-map-frame"
                src="{{ $embedUrl }}"
                title="Map of {{ $usableVenue }}"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
        @endif

        <div class="venue-map-body">
            <div class="list-row-main">
                <div class="card-icon">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="detail-label">Venue</div>
                    <div class="detail-value">{{ $usableVenue }}</div>
                    @unless ($embedUrl)
                        <div class="list-caption">Map preview unavailable — use the directions link.</div>
                    @endunless
                </div>
            </div>

            <a href="{{ $directionsUrl }}" class="button button-light button-sm" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i>
                Get Directions
            </a>
        </div>
    </div>
@endif

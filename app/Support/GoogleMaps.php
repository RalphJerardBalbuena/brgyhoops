<?php

namespace App\Support;

/**
 * Builds Google Maps URLs for a free-text venue.
 *
 * Only the Maps Embed API (iframe) and plain maps links are used — no
 * JavaScript API, Places, or geocoding.
 */
class GoogleMaps
{
    /**
     * Placeholder venue values that should not be shown on a map.
     */
    private const PLACEHOLDERS = ['tba', 'tbd', 'to be announced', 'n/a', 'na', 'none'];

    /**
     * Return a cleaned venue string, or null when it is empty or a placeholder.
     */
    public static function usableVenue(?string $venue): ?string
    {
        $venue = trim(preg_replace('/\s+/', ' ', (string) $venue));

        if ($venue === '' || in_array(strtolower($venue), self::PLACEHOLDERS, true)) {
            return null;
        }

        return $venue;
    }

    /**
     * The configured Embed API key, or null when not configured.
     */
    public static function embedKey(): ?string
    {
        $key = config('services.google_maps.embed_key');

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    /**
     * Embed iframe URL for the venue, or null when no key or usable venue.
     */
    public static function embedUrl(?string $venue): ?string
    {
        $venue = self::usableVenue($venue);
        $key = self::embedKey();

        if ($venue === null || $key === null) {
            return null;
        }

        return 'https://www.google.com/maps/embed/v1/place?' . http_build_query([
            'key' => $key,
            'q' => $venue,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Whether both values are numeric and within latitude/longitude ranges.
     */
    public static function validCoordinates(mixed $latitude, mixed $longitude): bool
    {
        return is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90 && (float) $latitude <= 90
            && (float) $longitude >= -180 && (float) $longitude <= 180;
    }

    /**
     * Plain decimal string for a coordinate (max 7 places, no trailing zeros,
     * never scientific notation).
     */
    public static function formatCoordinate(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }

    /**
     * Google Maps directions link to exact coordinates (no API key needed),
     * e.g. https://www.google.com/maps/dir/?api=1&destination=14.5995,120.9842
     */
    public static function coordinateDirectionsUrl(mixed $latitude, mixed $longitude): ?string
    {
        if (!self::validCoordinates($latitude, $longitude)) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='
            . self::formatCoordinate((float) $latitude) . ','
            . self::formatCoordinate((float) $longitude);
    }

    /**
     * Google Maps directions link for the venue (no API key needed).
     */
    public static function directionsUrl(?string $venue): ?string
    {
        $venue = self::usableVenue($venue);

        if ($venue === null) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?' . http_build_query([
            'api' => 1,
            'destination' => $venue,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}

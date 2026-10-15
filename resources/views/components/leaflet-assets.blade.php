{{--
    Leaflet 1.9.4 (BSD-2-Clause) from cdnjs — the same CDN the layout already
    uses for Font Awesome. Pinned version + Subresource Integrity hashes, so a
    tampered or changed file is refused by the browser. If the CDN is
    unreachable, public/js/brgyhoops-maps.js keeps the text/coordinate
    fallbacks visible instead of a broken map.

    Map tiles: OpenStreetMap standard tile server (no API key). Attribution is
    always shown on the map, per https://operations.osmfoundation.org/policies/tiles/
--}}
@pushOnce('styles')
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
        integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >
@endPushOnce

@pushOnce('scripts')
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"
        integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
        defer
    ></script>
    <script src="{{ asset('js/brgyhoops-maps.js') }}" defer></script>
@endPushOnce

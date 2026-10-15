/* =========================================================
   BrgyHoops game venue maps (Leaflet + OpenStreetMap tiles)

   - [data-location-picker]: admin game form. Click the map to place the pin,
     drag it, or type exact coordinates into the latitude/longitude fields.
   - [data-game-map]: compact read-only map for a pinned game on public pages.

   Progressive enhancement: without Leaflet (CDN blocked/offline) or without
   JavaScript, the coordinate fields and fallback text keep working.
   ========================================================= */
(function () {
    'use strict';

    // OpenStreetMap standard tiles: attribution must stay visible.
    // Policy: https://operations.osmfoundation.org/policies/tiles/
    var TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    var ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
    var DEFAULT_CENTER = [12.8797, 121.774]; // Philippines
    var DEFAULT_ZOOM = 6;
    var PIN_ZOOM = 17;

    function leafletAvailable() {
        return typeof window.L !== 'undefined' && typeof window.L.map === 'function';
    }

    function tileLayer() {
        return window.L.tileLayer(TILE_URL, {
            maxZoom: 19,
            attribution: ATTRIBUTION
        });
    }

    function pinIcon() {
        return window.L.divIcon({
            className: 'bh-map-pin',
            html: '<span aria-hidden="true"></span>',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        });
    }

    function parseCoordinate(value, limit) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return null;
        }

        var number = Number(value);

        return isFinite(number) && Math.abs(number) <= limit ? number : null;
    }

    // Max 7 decimal places (matches the database column), no trailing zeros.
    function formatCoordinate(number) {
        var formatted = (Math.round(number * 1e7) / 1e7).toFixed(7).replace(/\.?0+$/, '');

        return formatted === '-0' ? '0' : formatted;
    }

    function setStatus(element, message) {
        var status = element.querySelector('[data-map-status]');

        if (status) {
            status.textContent = message;
        }
    }

    function showUnavailable(container, message) {
        var fallback = container.querySelector('.map-fallback');

        if (fallback) {
            fallback.textContent = message;
        }

        container.classList.add('map-unavailable');
    }

    /* ---------------------------------------------------------
       Admin picker
       --------------------------------------------------------- */
    function initPicker(picker) {
        var canvas = picker.querySelector('[data-map-canvas]');
        var latInput = document.getElementById(picker.getAttribute('data-lat-input'));
        var lngInput = document.getElementById(picker.getAttribute('data-lng-input'));
        var clearButton = picker.querySelector('[data-clear-pin]');

        if (!canvas || !latInput || !lngInput) {
            return;
        }

        if (!leafletAvailable()) {
            showUnavailable(canvas, 'The interactive map could not be loaded. You can still type the latitude and longitude below.');
            return;
        }

        canvas.innerHTML = '';

        var map = window.L.map(canvas, { scrollWheelZoom: false });
        var marker = null;

        tileLayer().addTo(map);

        function writeInputs(latLng) {
            latInput.value = formatCoordinate(latLng.lat);
            lngInput.value = formatCoordinate(latLng.lng);
            setStatus(picker, 'Pin placed at ' + latInput.value + ', ' + lngInput.value + '.');
        }

        function placeMarker(latLng) {
            if (marker) {
                marker.setLatLng(latLng);
            } else {
                marker = window.L.marker(latLng, {
                    icon: pinIcon(),
                    draggable: true,
                    keyboard: true,
                    title: 'Venue pin (drag to adjust)',
                    alt: 'Venue pin'
                }).addTo(map);

                marker.on('dragend', function () {
                    writeInputs(marker.getLatLng().wrap());
                });
            }

            if (clearButton) {
                clearButton.hidden = false;
            }
        }

        function removeMarker() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }

            if (clearButton) {
                clearButton.hidden = true;
            }
        }

        var savedLat = parseCoordinate(latInput.value, 90);
        var savedLng = parseCoordinate(lngInput.value, 180);

        if (savedLat !== null && savedLng !== null) {
            placeMarker([savedLat, savedLng]);
            map.setView([savedLat, savedLng], PIN_ZOOM);
        } else {
            map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
        }

        map.on('click', function (event) {
            var latLng = event.latlng.wrap();

            placeMarker(latLng);
            writeInputs(latLng);
        });

        // Typing exact coordinates moves the pin; clearing both removes it.
        function syncFromInputs() {
            var lat = parseCoordinate(latInput.value, 90);
            var lng = parseCoordinate(lngInput.value, 180);

            if (lat !== null && lng !== null) {
                placeMarker([lat, lng]);
                map.panTo([lat, lng]);
                setStatus(picker, 'Pin moved to ' + latInput.value + ', ' + lngInput.value + '.');
            } else if (latInput.value.trim() === '' && lngInput.value.trim() === '') {
                removeMarker();
                setStatus(picker, 'Pin removed.');
            }
        }

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                latInput.value = '';
                lngInput.value = '';
                removeMarker();
                setStatus(picker, 'Pin removed.');
            });
        }

        // The map may initialise before layout settles.
        window.setTimeout(function () {
            map.invalidateSize();
        }, 150);
    }

    /* ---------------------------------------------------------
       Public compact maps (lazy: only when scrolled into view)
       --------------------------------------------------------- */
    function initDisplayMap(container) {
        var lat = parseCoordinate(container.getAttribute('data-lat'), 90);
        var lng = parseCoordinate(container.getAttribute('data-lng'), 180);

        if (lat === null || lng === null) {
            return;
        }

        if (!leafletAvailable()) {
            showUnavailable(container, 'Map unavailable — pinned location: ' + formatCoordinate(lat) + ', ' + formatCoordinate(lng));
            return;
        }

        container.innerHTML = '';

        var map = window.L.map(container, {
            scrollWheelZoom: false,
            zoomControl: true
        }).setView([lat, lng], 16);

        tileLayer().addTo(map);

        var marker = window.L.marker([lat, lng], {
            icon: pinIcon(),
            keyboard: false,
            alt: container.getAttribute('data-label') || 'Game venue'
        }).addTo(map);

        var label = document.createElement('strong');
        label.textContent = container.getAttribute('data-label') || 'Game venue';
        marker.bindPopup(label);
    }

    function initDisplayMaps() {
        var containers = Array.prototype.slice.call(document.querySelectorAll('[data-game-map]'));

        if (!('IntersectionObserver' in window)) {
            containers.forEach(initDisplayMap);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    initDisplayMap(entry.target);
                }
            });
        }, { rootMargin: '200px' });

        containers.forEach(function (container) {
            observer.observe(container);
        });
    }

    function init() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-location-picker]'), initPicker);
        initDisplayMaps();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

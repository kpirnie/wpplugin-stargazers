/**
 * Light Pollution Map
 * 
 * Initializes Leaflet maps with light pollution overlay
 * 
 * @package US Star Gazers
 * @since 8.4
 */
(function () {
    'use strict';

    function initMap(config) {
        var mapEl = document.getElementById(config.mapId);
        if (!mapEl || mapEl._leaflet_id) {
            return;
        }

        // Initialize map
        var map = L.map(config.mapId, {
            center: [config.lat, config.lng],
            zoom: 8,
            scrollWheelZoom: true
        });

        // Dark base layer (CartoDB Dark Matter)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // NASA GIBS Black Marble / VIIRS Nighttime Lights overlay
        var lpLayer = L.tileLayer('https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/VIIRS_Black_Marble/default/2016-01-01/GoogleMapsCompatible_Level8/{z}/{y}/{x}.png', {
            attribution: '&copy; <a href="https://earthdata.nasa.gov/gibs">NASA GIBS</a> Black Marble',
            opacity: 0.25,
            maxZoom: 8
        }).addTo(map);

        // Add marker for user location
        var marker = L.marker([config.lat, config.lng]).addTo(map);
        marker.bindPopup(config.popupContent).openPopup();

        // Opacity control
        var OpacityControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function () {
                var container = L.DomUtil.create('div', 'sgu-lp-opacity-control');
                container.innerHTML =
                    '<label>Opacity<br>' +
                    '<input type="range" min="0" max="100" value="25">' +
                    '</label>';
                L.DomEvent.disableClickPropagation(container);

                var slider = container.querySelector('input');
                slider.addEventListener('input', function () {
                    lpLayer.setOpacity(this.value / 100);
                });

                return container;
            }
        });

        map.addControl(new OpacityControl());
    }

    function initAllMaps() {
        if (typeof L === 'undefined' || !window.sguLightPollutionMaps) {
            return;
        }

        window.sguLightPollutionMaps.forEach(initMap);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllMaps);
    } else {
        initAllMaps();
    }

})();
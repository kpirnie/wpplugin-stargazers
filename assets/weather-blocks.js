/**
 * Weather Blocks AJAX Loader
 * 
 * Discovers all weather block placeholders on the page, batches
 * them into a single AJAX request, and swaps in the rendered HTML.
 * Supports refresh without full page reload by caching discovered
 * block configs and their DOM elements after first load.
 * 
 * @package US Star Gazers
 * @since 8.4
 */
( function () {
    'use strict';

    // Persists block configs after first discovery for use on refresh
    var discoveredBlocks   = [];
    var discoveredElements = [];

    /**
     * Get the user's location from the SGU weather cookie.
     * Returns null if cookie is not present or malformed.
     * 
     * @returns {Object|null}
     */
    function getStoredLocation() {
        const name    = ( sguWeather.cookieName || 'sgu_weather_location' ) + '=';
        const cookies = document.cookie.split( ';' );

        for ( let cookie of cookies ) {
            cookie = cookie.trim();
            if ( cookie.indexOf( name ) === 0 ) {
                try {
                    return JSON.parse( decodeURIComponent( cookie.substring( name.length ) ) );
                } catch ( e ) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Swap placeholder content with rendered HTML from the server.
     * Caches the uid on the element before removing the data attribute
     * so refresh calls can still match blocks to their containers.
     * 
     * @param {HTMLElement} el   The placeholder element
     * @param {string}      html The rendered HTML to insert
     */
    function swapContent( el, html ) {
        // Cache the uid before removing the attribute so refresh can still use it
        el.dataset.sguUidCache = el.getAttribute( 'data-sgu-uid' );
        el.innerHTML           = html;
        el.removeAttribute( 'aria-busy' );
        el.removeAttribute( 'data-sgu-weather-block' );
        el.removeAttribute( 'data-sgu-uid' );
        el.removeAttribute( 'data-sgu-attrs' );
        el.classList.remove( 'sgu-weather-placeholder' );
    }

    /**
     * Show an error state with location controls and retry option.
     * Includes the location form so the user can change location or
     * retry without a full page reload.
     * 
     * @param {HTMLElement} el      The block container element
     * @param {string}      message Error message to display
     */
    function showError( el, message ) {
        var template = document.getElementById( 'sgu-location-bar-template' );
        var bar      = template ? template.innerHTML : '';
        el.innerHTML = '<div class="sgu-weather-error-state">'
            + bar
            + '<p class="sgu-weather-load-error">' + message + '</p>'
            + '<button type="button" class="uk-button uk-button-text" onclick="window.sguLoadWeatherBlocks()">↻ Retry</button>'
            + '</div>';
        el.removeAttribute( 'aria-busy' );
    }

    /**
     * Fire the batch AJAX request for a set of blocks.
     * Separated from loadWeatherBlocks so refresh can call it
     * directly without needing placeholders in the DOM.
     * 
     * @param {Array} blocks   Array of {type, uid, attributes}
     * @param {Array} elements Matching array of DOM elements
     */
    function fireBatchRequest( blocks, elements ) {

        var location = getStoredLocation();

        // No location — show prompt in each container and bail
        if ( ! location || ! location.lat || ! location.lon ) {
            elements.forEach( function ( el ) {
                el.innerHTML = '<p class="sgu-weather-load-error">Please set your location to view weather data.</p>';
                el.removeAttribute( 'aria-busy' );
            } );
            return;
        }

        // Build POST body for the batch request
        const formData = new FormData();
        formData.append( 'action', sguWeather.batchAction );
        formData.append( 'nonce',  sguWeather.nonce );
        formData.append( 'lat',    location.lat );
        formData.append( 'lon',    location.lon );

        blocks.forEach( function ( block, i ) {
            formData.append( 'blocks[' + i + '][type]',       block.type );
            formData.append( 'blocks[' + i + '][uid]',        block.uid );
            formData.append( 'blocks[' + i + '][attributes]', JSON.stringify( block.attributes ) );
        } );

        fetch( sguWeather.ajaxUrl, {
            method:      'POST',
            credentials: 'same-origin',
            body:        formData,
        } )
        .then( function ( response ) {
            if ( ! response.ok ) {
                throw new Error( 'Network error: ' + response.status );
            }
            return response.json();
        } )
        .then( function ( data ) {
            if ( ! data.success || ! data.data || ! data.data.blocks ) {
                elements.forEach( function ( el ) {
                    showError( el, 'Weather data could not be loaded.' );
                } );
                return;
            }

            // Swap each container with its rendered HTML fragment
            // On refresh, uid is stored in dataset.sguUidCache since the attribute was removed
            elements.forEach( function ( el ) {
                const uid  = el.getAttribute( 'data-sgu-uid' ) || el.dataset.sguUidCache;
                const html = data.data.blocks[ uid ];

                if ( html !== undefined ) {
                    swapContent( el, html );
                } else {
                    showError( el, 'Weather data unavailable.' );
                }
            } );
        } )
        .catch( function ( err ) {
            elements.forEach( function ( el ) {
                showError( el, 'Weather data could not be loaded.' );
            } );
            console.error( 'SGU Weather:', err );
        } );
    }

    /**
     * Discover all weather block placeholders, cache their configs,
     * and fire the batch request. On subsequent calls (refresh), 
     * re-uses the cached elements and configs, restoring loading 
     * spinners before firing again.
     */
    function loadWeatherBlocks() {

        const placeholders = document.querySelectorAll( '[data-sgu-weather-block]' );

        // On refresh, placeholders are gone — re-use cached data
        if ( ! placeholders.length && discoveredBlocks.length ) {
            discoveredElements.forEach( function ( el ) {
                el.innerHTML = '<div class="sgu-weather-loading"><span class="sgu-loading-spinner" aria-hidden="true"></span></div>';
                el.setAttribute( 'aria-busy', 'true' );
            } );
            fireBatchRequest( discoveredBlocks, discoveredElements );
            return;
        }

        if ( ! placeholders.length ) {
            return;
        }

        // First load — discover and cache blocks and their elements
        discoveredElements = Array.from( placeholders );
        discoveredBlocks   = [];

        placeholders.forEach( function ( el ) {
            const type = el.getAttribute( 'data-sgu-weather-block' );
            const uid  = el.getAttribute( 'data-sgu-uid' );
            let attrs  = {};

            try {
                attrs = JSON.parse( el.getAttribute( 'data-sgu-attrs' ) || '{}' );
            } catch ( e ) {
                attrs = {};
            }

            discoveredBlocks.push( { type: type, uid: uid, attributes: attrs } );
        } );

        fireBatchRequest( discoveredBlocks, discoveredElements );
    }

    // Expose for external callers (e.g., the refresh button in location.js)
    window.sguLoadWeatherBlocks = loadWeatherBlocks;

    // Wait for DOM ready before discovering placeholders
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', loadWeatherBlocks );
    } else {
        loadWeatherBlocks();
    }

} )();
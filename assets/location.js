/**
 * SGU Weather Location Handler
 * 
 * @package US Star Gazers
 * @since 8.4
 */

(function ($) {
    'use strict';

    const SGUWeatherLocation = {

        init: function () {
            this.bindEvents();
            this.checkInitialLocation();
        },

        bindEvents: function () {
            $(document).on('click', '.sgu-weather-geolocate', this.requestGeolocation.bind(this));
            $(document).on('submit', '.sgu-weather-zip-form', this.handleZipSubmit.bind(this));
            $(document).on('click', '.sgu-weather-change-location', this.showLocationPicker.bind(this));
            $(document).on('click', '.sgu-weather-cancel-change', this.hideLocationPicker.bind(this));
            $(document).on('click', '.sgu-weather-refresh', this.refreshWeather.bind(this));
        },

        checkInitialLocation: function () {
            const location = this.getStoredLocation();
            if (location) {
                this.updateLocationDisplay(location);
            } else if (typeof sguWeather !== 'undefined' && !sguWeather.hasLocation) {
                this.promptForLocation();
            }
        },

        promptForLocation: function () {
            $('.sgu-weather-location-prompt').show().addClass('active');
            $('.sgu-weather-has-location').hide();
        },

        requestGeolocation: function (e) {
            if (e) e.preventDefault();

            const $button = $('.sgu-weather-geolocate');
            const originalText = $button.text();

            if (!('geolocation' in navigator)) {
                this.showError('Geolocation is not supported by your browser. Please enter a ZIP code.');
                return;
            }

            $button.text('Locating...').prop('disabled', true);

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    this.saveLocation(lat, lon, 'geolocation');
                    $button.text(originalText).prop('disabled', false);
                },
                (error) => {
                    $button.text(originalText).prop('disabled', false);
                    let message = 'Unable to get your location. ';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            message += 'Location access was denied. Please enter a ZIP code instead.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message += 'Location information unavailable. Please enter a ZIP code.';
                            break;
                        case error.TIMEOUT:
                            message += 'Location request timed out. Please try again or enter a ZIP code.';
                            break;
                        default:
                            message += 'Please enter a ZIP code instead.';
                    }
                    this.showError(message);
                },
                {
                    enableHighAccuracy: false,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        },

        handleZipSubmit: function (e) {
            e.preventDefault();

            const $form = $(e.target);
            const $input = $form.find('.sgu-weather-zip-input');
            const $button = $form.find('button[type="submit"]');
            const zip = $input.val().trim();

            if (!/^\d{5}$/.test(zip)) {
                this.showError('Please enter a valid 5-digit ZIP code.');
                $input.focus();
                return;
            }

            const originalText = $button.text();
            $button.text('Finding...').prop('disabled', true);

            $.ajax({
                url: sguWeather.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sgu_geocode_zip',
                    nonce: sguWeather.nonce,
                    zip: zip
                },
                success: (response) => {
                    $button.text(originalText).prop('disabled', false);
                    if (response.success) {
                        const location = response.data.location;
                        this.storeLocation(location);
                        this.updateLocationDisplay(location);
                        this.hideLocationPicker();
                        window.location.reload();
                    } else {
                        this.showError(response.data.message || 'Could not find that ZIP code.');
                    }
                },
                error: () => {
                    $button.text(originalText).prop('disabled', false);
                    this.showError('Network error. Please try again.');
                }
            });
        },

        saveLocation: function (lat, lon, source) {
            $.ajax({
                url: sguWeather.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sgu_save_location',
                    nonce: sguWeather.nonce,
                    lat: lat,
                    lon: lon
                },
                success: (response) => {
                    if (response.success) {
                        const location = response.data.location;
                        this.storeLocation(location);
                        this.updateLocationDisplay(location);
                        this.hideLocationPicker();
                        window.location.reload();
                    } else {
                        this.showError(response.data.message || 'Could not save location.');
                    }
                },
                error: () => {
                    this.showError('Network error. Please try again.');
                }
            });
        },

        storeLocation: function (location) {
            const cookieValue = JSON.stringify(location);
            const days = (typeof sguWeather !== 'undefined' && sguWeather.cookieDays) ? sguWeather.cookieDays : 30;
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            const cookieName = (typeof sguWeather !== 'undefined' && sguWeather.cookieName) ? sguWeather.cookieName : 'sgu_weather_location';

            document.cookie = cookieName + '=' + encodeURIComponent(cookieValue) +
                ';expires=' + expires.toUTCString() +
                ';path=/;SameSite=Lax';
        },

        getStoredLocation: function () {
            const cookieName = (typeof sguWeather !== 'undefined' && sguWeather.cookieName) ? sguWeather.cookieName : 'sgu_weather_location';
            const name = cookieName + '=';
            const cookies = document.cookie.split(';');

            for (let cookie of cookies) {
                cookie = cookie.trim();
                if (cookie.indexOf(name) === 0) {
                    try {
                        return JSON.parse(decodeURIComponent(cookie.substring(name.length)));
                    } catch (e) {
                        return null;
                    }
                }
            }
            return null;
        },

        updateLocationDisplay: function (location) {
            let displayName = location.name || '';
            if (location.state) {
                displayName += (displayName ? ', ' : '') + location.state;
            }
            $('.sgu-weather-location-name').text(displayName);
            $('.sgu-weather-has-location').show();
            $('.sgu-weather-no-location').hide();
            $('.sgu-weather-location-prompt').removeClass('active').hide();
        },

        showLocationPicker: function (e) {
            if (e) e.preventDefault();
            $('.sgu-weather-has-location').hide();
            $('.sgu-weather-location-prompt').show().addClass('active');
        },

        hideLocationPicker: function (e) {
            if (e) e.preventDefault();
            $('.sgu-weather-location-prompt').removeClass('active').hide();
            $('.sgu-weather-has-location').show();
        },

        refreshWeather: function (e) {
            if (e) e.preventDefault();
            // Re-trigger the AJAX batch loader instead of a full page reload
            if ( typeof window.sguLoadWeatherBlocks === 'function' ) {
                window.sguLoadWeatherBlocks();
            }
        },

        showError: function (message) {
            $('.sgu-weather-error').remove();
            const $error = $('<div class="sgu-weather-error uk-alert uk-alert-danger">' +
                '<a class="uk-alert-close" uk-close></a>' +
                '<p>' + message + '</p></div>');
            $('.sgu-weather-location-prompt, .sgu-weather-container').first().prepend($error);
            setTimeout(() => {
                $error.fadeOut(() => $error.remove());
            }, 5000);
        },

        showContainerError: function ($container, message) {
            const $error = $container.find('.sgu-weather-error');
            if ($error.length) {
                $error.text(message).show();
            } else {
                $container.find('.sgu-weather-content')
                    .prepend('<div class="sgu-weather-error uk-alert uk-alert-warning">' + message + '</div>');
            }
        }
    };

    $(document).ready(function () {
        SGUWeatherLocation.init();
    });

    window.SGUWeatherLocation = SGUWeatherLocation;

})(jQuery);
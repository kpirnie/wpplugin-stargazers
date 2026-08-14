<?php

/** 
 * Weather Location Handler
 * 
 * Handles location detection, storage (cookies), and retrieval
 * for weather functionality.
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers
 * 
 */

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

if (! class_exists('SGU_Weather_Location')) {

    /** 
     * Class SGU_Weather_Location
     * 
     * Manages user location for weather features including:
     * - Browser geolocation permission handling
     * - ZIP code fallback
     * - Cookie storage for persistence
     * - AJAX endpoints for location updates
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers
     * 
     */
    class SGU_Weather_Location
    {

        /** 
         * @var string Cookie name for storing location data
         */
        private const COOKIE_NAME = 'sgu_weather_location';

        /** 
         * @var int Cookie expiration in days
         */
        private const COOKIE_DAYS = 30;

        /** 
         * init
         * 
         * Initialize the location handler
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function init(): void
        {

            // Register AJAX handlers for logged-in and non-logged-in users
            add_action('wp_ajax_sgu_save_location', [$this, 'ajax_save_location']);
            add_action('wp_ajax_nopriv_sgu_save_location', [$this, 'ajax_save_location']);

            // Register AJAX handler for getting weather by ZIP
            add_action('wp_ajax_sgu_geocode_zip', [$this, 'ajax_geocode_zip']);
            add_action('wp_ajax_nopriv_sgu_geocode_zip', [$this, 'ajax_geocode_zip']);

            // Register AJAX handler for fetching weather data
            add_action('wp_ajax_sgu_get_weather', [$this, 'ajax_get_weather']);
            add_action('wp_ajax_nopriv_sgu_get_weather', [$this, 'ajax_get_weather']);

            // Enqueue location JavaScript on front-end
            add_action('wp_enqueue_scripts', [$this, 'enqueue_location_scripts']);

            // Register AJAX handler for batch weather block rendering
            add_action('wp_ajax_sgu_fetch_weather_blocks', [$this, 'ajax_fetch_weather_blocks']);
            add_action('wp_ajax_nopriv_sgu_fetch_weather_blocks', [$this, 'ajax_fetch_weather_blocks']);
        }

        /** 
         * enqueue_location_scripts
         * 
         * Enqueue the JavaScript for location detection
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function enqueue_location_scripts(): void
        {

            // Only enqueue if we have weather shortcodes/blocks on the page
            // We'll enqueue it globally for now, optimization can come later
            wp_enqueue_script(
                'sgu-weather-location',
                plugins_url('assets/location.js', SGUP_PATH . '/' . SGUP_FILENAME),
                ['jquery'],
                '1.0.0',
                true
            );

            // Localize script with AJAX URL and nonce
            wp_localize_script('sgu-weather-location', 'sguWeather', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sgu_weather_nonce'),
                'hasLocation' => (bool) $this->get_stored_location(),
                'cookieName' => self::COOKIE_NAME,
                'cookieDays' => self::COOKIE_DAYS,
                'batchAction'  => 'sgu_fetch_weather_blocks',
            ]);

            // sgu-weather-location must load first so sguWeather object exists
            wp_enqueue_script(
                'sgu-weather-blocks',
                plugins_url('assets/weather-blocks.js', SGUP_PATH . '/' . SGUP_FILENAME),
                ['jquery', 'sgu-weather-location'],
                '1.0.0',
                true
            );
        }

        /** 
         * get_stored_location
         * 
         * Retrieve stored location from cookie
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return object|bool Location object or false if not stored
         * 
         */
        public function get_stored_location(): object|bool
        {

            // Check if cookie exists
            if (! isset($_COOKIE[self::COOKIE_NAME])) {
                return false;
            }

            // Decode and validate the cookie data
            $data = json_decode(stripslashes($_COOKIE[self::COOKIE_NAME]), true);

            if (! $data || ! isset($data['lat']) || ! isset($data['lon'])) {
                return false;
            }

            // Return as object
            return (object) [
                'lat' => (float) $data['lat'],
                'lon' => (float) $data['lon'],
                'name' => sanitize_text_field($data['name'] ?? ''),
                'state' => sanitize_text_field($data['state'] ?? ''),
                'zip' => sanitize_text_field($data['zip'] ?? ''),
                'source' => sanitize_text_field($data['source'] ?? 'unknown'),
            ];
        }

        /** 
         * has_stored_location
         * 
         * Check if user has a stored location
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return bool True if location is stored
         * 
         */
        public function has_stored_location(): bool
        {
            return isset($_COOKIE[self::COOKIE_NAME]);
        }

        /** 
         * ajax_save_location
         * 
         * AJAX handler for saving location from browser geolocation
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function ajax_save_location(): void
        {

            // Verify nonce
            if (! wp_verify_nonce($_POST['nonce'] ?? '', 'sgu_weather_nonce')) {
                wp_send_json_error(['message' => 'Invalid security token']);
            }

            // Validate coordinates
            $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
            $lon = isset($_POST['lon']) ? (float) $_POST['lon'] : null;

            if ($lat === null || $lon === null) {
                wp_send_json_error(['message' => 'Invalid coordinates']);
            }

            // Validate lat/lon ranges
            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                wp_send_json_error(['message' => 'Coordinates out of range']);
            }

            // Get location name via reverse geocoding
            $weather_data = new SGU_Weather_Data();
            $location = $weather_data->reverse_geocode($lat, $lon);

            // Build location data for cookie
            $location_data = [
                'lat' => $lat,
                'lon' => $lon,
                'name' => $location->name ?? '',
                'state' => $location->state ?? '',
                'zip' => '',
                'source' => 'geolocation',
                'timestamp' => time(),
            ];

            // Set the cookie (via JavaScript response since we're in AJAX)
            wp_send_json_success([
                'message' => 'Location saved successfully',
                'location' => $location_data,
            ]);
        }

        /** 
         * ajax_geocode_zip
         * 
         * AJAX handler for geocoding a ZIP code
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function ajax_geocode_zip(): void
        {

            // Verify nonce
            if (! wp_verify_nonce($_POST['nonce'] ?? '', 'sgu_weather_nonce')) {
                wp_send_json_error(['message' => 'Invalid security token']);
            }

            // Get and validate ZIP code
            $zip = sanitize_text_field($_POST['zip'] ?? '');
            $zip = preg_replace('/[^0-9]/', '', $zip);

            if (strlen($zip) !== 5) {
                wp_send_json_error(['message' => 'Please enter a valid 5-digit ZIP code']);
            }

            // Geocode the ZIP
            $weather_data = new SGU_Weather_Data();
            $location = $weather_data->geocode_zip($zip);

            if (! $location) {
                wp_send_json_error(['message' => 'Could not find location for this ZIP code']);
            }

            // Build location data for cookie
            $location_data = [
                'lat' => $location->lat,
                'lon' => $location->lon,
                'name' => $location->name,
                'state' => $location->state ?? '',
                'zip' => $zip,
                'source' => 'zipcode',
                'timestamp' => time(),
            ];

            wp_send_json_success([
                'message' => 'Location found',
                'location' => $location_data,
            ]);
        }

        /** 
         * ajax_get_weather
         * 
         * AJAX handler for fetching weather data
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function ajax_get_weather(): void
        {

            // Verify nonce
            if (! wp_verify_nonce($_POST['nonce'] ?? '', 'sgu_weather_nonce')) {
                wp_send_json_error(['message' => 'Invalid security token']);
            }

            // Get coordinates
            $lat = isset($_POST['lat']) ? (float) $_POST['lat'] : null;
            $lon = isset($_POST['lon']) ? (float) $_POST['lon'] : null;
            $type = sanitize_text_field($_POST['type'] ?? 'current');

            if ($lat === null || $lon === null) {
                wp_send_json_error(['message' => 'No location provided']);
            }

            // Get weather data
            $weather_data = new SGU_Weather_Data();

            $data = match ($type) {
                'current' => $weather_data->get_current_weather($lat, $lon),
                'daily' => $weather_data->get_daily_forecast($lat, $lon),
                'hourly' => $weather_data->get_hourly_forecast($lat, $lon),
                'weekly' => $weather_data->get_daily_forecast($lat, $lon),
                'noaa' => $weather_data->get_noaa_forecast($lat, $lon),
                'alerts' => $weather_data->get_noaa_alerts($lat, $lon),
                default => null,
            };

            // Handle different return types - alerts returns array, others return object|bool
            if ($type === 'alerts') {
                // Alerts returns an array, empty array is valid (no alerts)
                wp_send_json_success([
                    'weather' => $data,
                    'type' => $type,
                ]);
            }

            // For other types, check if we got valid data
            if ($data === false || $data === null) {
                wp_send_json_error(['message' => 'Could not retrieve weather data. Please check your API keys are configured.']);
            }

            wp_send_json_success([
                'weather' => $data,
                'type' => $type,
            ]);
        }

        /** 
         * ajax_fetch_weather_blocks
         * 
         * AJAX handler for batch rendering weather block HTML fragments.
         * Accepts an array of block types with attributes, renders each
         * template server-side, and returns keyed HTML fragments.
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         */
        public function ajax_fetch_weather_blocks(): void
        {

            // Verify nonce
            if (! wp_verify_nonce($_POST['nonce'] ?? '', 'sgu_weather_nonce')) {
                wp_send_json_error(['message' => 'Invalid security token']);
            }

            // Validate coordinates — empty strings must be caught before float cast
            $lat_raw = trim($_POST['lat'] ?? '');
            $lon_raw = trim($_POST['lon'] ?? '');

            if ($lat_raw === '' || $lon_raw === '') {
                wp_send_json_error(['message' => 'No location provided']);
            }

            $lat = (float) $lat_raw;
            $lon = (float) $lon_raw;

            // Get requested blocks — array of {type, attributes} objects
            $blocks = isset($_POST['blocks']) ? (array) $_POST['blocks'] : [];

            if (empty($blocks)) {
                wp_send_json_error(['message' => 'No blocks requested']);
            }

            // Instantiate data handlers once for all blocks in this request
            $weather_data     = new SGU_Weather_Data();
            $location_handler = new SGU_Weather_Location();
            $location         = $location_handler->get_stored_location();
            $location_name    = $location?->name ?? '';

            // Pre-fetch only the data types actually needed by the requested blocks
            $needed = array_column($blocks, 'type');
            $cache  = [];

            // Map block types to their required data type(s)
            $type_map = [
                'weather-current'           => ['current'],
                'weather-daily'             => ['hourly', 'noaa'],
                'weather-daily-hourly'      => ['hourly'],
                'weather-weekly'            => ['daily', 'noaa'],
                'weather-weekly-extended'   => ['noaa'],
                'weather-alerts'            => ['alerts'],
                'weather-full'              => ['current', 'hourly', 'daily', 'noaa', 'alerts'],
            ];

            // Collect unique data types needed across all requested blocks
            $data_types = [];
            foreach ($needed as $block_type) {
                $data_types = array_merge($data_types, $type_map[$block_type] ?? []);
            }
            $data_types = array_unique($data_types);

            // Fetch each unique data type once and cache in memory
            foreach ($data_types as $data_type) {
                $cache[$data_type] = match ($data_type) {
                    'current' => $weather_data->get_current_weather($lat, $lon),
                    'hourly'  => $weather_data->get_hourly_forecast($lat, $lon),
                    'daily'   => $weather_data->get_daily_forecast($lat, $lon),
                    'noaa'    => $weather_data->get_noaa_forecast($lat, $lon),
                    'alerts'  => $weather_data->get_noaa_alerts($lat, $lon),
                    default   => null,
                };
            }

            // Render each requested block and collect HTML fragments
            $rendered = [];

            foreach ($blocks as $block) {

                $type  = sanitize_key($block['type'] ?? '');
                // Attributes are JSON-encoded by the JS layer — decode before use
                $raw_attrs = $block['attributes'] ?? '{}';
                $attrs     = is_string($raw_attrs)
                    ? (array) json_decode(wp_unslash($raw_attrs), true)
                    : (array) $raw_attrs;
                $uid   = sanitize_key($block['uid'] ?? $type);

                // Build the base data array shared by all weather templates
                $base = [
                    'title'                => sanitize_text_field($attrs['title'] ?? ''),
                    'show_title'           => filter_var($attrs['showTitle'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'show_location_picker' => filter_var($attrs['showLocationPicker'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    'has_location'         => (bool) $location,
                    'location'             => $location,
                    'location_name'        => $location_name,
                    'wrapper_attr'         => '',
                ];

                // Map block type to template path and merge its specific data
                $template_data = match ($type) {

                    'weather-current' => ['weather/current', $base + [
                        'show_details' => filter_var($attrs['showDetails'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'weather'      => $cache['current'] ?? null,
                    ]],

                    'weather-daily' => ['weather/day', $base + [
                        'show_hourly'   => filter_var($attrs['showHourly'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'hours_to_show' => absint($attrs['hoursToShow'] ?? 24),
                        'use_noaa'      => filter_var($attrs['useNoaa'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'forecast'      => $cache['hourly'] ?? null,
                        'noaa_forecast' => $cache['noaa'] ?? null,
                    ]],

                    'weather-daily-hourly' => ['weather/hourly', $base + [
                        'hours_to_show' => absint($attrs['hoursToShow'] ?? 24),
                        'forecast'      => $cache['hourly'] ?? null,
                    ]],

                    'weather-weekly' => ['weather/week', $base + [
                        'days_to_show'  => absint($attrs['daysToShow'] ?? 7),
                        'use_noaa'      => filter_var($attrs['useNoaa'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'forecast'      => $cache['daily'] ?? null,
                        'noaa_forecast' => $cache['noaa'] ?? null,
                    ]],

                    'weather-weekly-extended' => ['weather/week-extended', $base + [
                        'days_to_show' => absint($attrs['daysToShow'] ?? 7),
                        'weather'      => $cache['noaa'] ?? null,
                    ]],

                    'weather-alerts' => ['weather/alerts', $base + [
                        'max_alerts' => absint($attrs['maxAlerts'] ?? 5),
                        'alerts'     => array_slice((array) ($cache['alerts'] ?? []), 0, absint($attrs['maxAlerts'] ?? 5)),
                    ]],

                    'weather-full' => ['weather/dashboard', $base + [
                        'show_current'          => filter_var($attrs['showCurrent'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'show_hourly'           => filter_var($attrs['showHourly'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'show_daily'            => filter_var($attrs['showDaily'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'show_daily_extended'   => filter_var($attrs['showDailyExtended'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'show_alerts'           => filter_var($attrs['showAlerts'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'show_noaa'             => filter_var($attrs['showNoaa'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'current_weather'       => $cache['current'] ?? null,
                        'hourly_forecast'       => $cache['hourly'] ?? null,
                        'daily_forecast'        => $cache['daily'] ?? null,
                        'noaa_forecast'         => $cache['noaa'] ?? null,
                        'alerts'                => $cache['alerts'] ?? [],
                    ]],

                    default => null,
                };

                // Skip unknown block types
                if ($template_data === null) {
                    continue;
                }

                // Render the template and store under the block's unique ID
                [$template_path, $data] = $template_data;
                $rendered[$uid] = SGU_Static::render_template($template_path, $data);
            }

            wp_send_json_success(['blocks' => $rendered]);
        }

        /** 
         * get_default_location
         * 
         * Get a default location when none is available
         * Falls back to a central US location
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return object Default location object
         * 
         */
        public static function get_default_location(): object
        {
            return (object) [
                'lat' => 39.8283,
                'lon' => -98.5795,
                'name' => 'United States',
                'state' => '',
                'zip' => '',
                'source' => 'default',
            ];
        }
    }
}

<?php
/** 
 * Weather Data
 * 
 * This file contains the weather data methods for fetching and processing
 * weather information from Open-Meteo and NOAA APIs.
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

if( ! class_exists( 'SGU_Weather_Data' ) ) {

    /** 
     * Class SGU_Weather_Data
     * 
     * Handles all weather data operations using Open-Meteo and NOAA APIs.
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers
     * 
    */
    class SGU_Weather_Data {

        /** 
         * @var array In-memory cache of grid point data
         */
        private array $grid_cache = [];

        /** 
         * @var string Open-Meteo API base URL
         */
        private const OPEN_METEO_API = 'https://api.open-meteo.com/v1/forecast';

        /** 
         * @var string Open-Meteo Geocoding API base URL
         */
        private const OPEN_METEO_GEOCODING_API = 'https://geocoding-api.open-meteo.com/v1/search';

        /** 
         * get_current_weather
         * 
         * Get current weather conditions from Open-Meteo API
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return object|bool Weather data object or false on failure
         * 
        */
        public function get_current_weather( float $lat, float $lon ) : object|bool {

            // Build cache key
            $cache_key = sprintf( 'sgu_openmeteo_current_%s_%s', round( $lat, 2 ), round( $lon, 2 ) );
            
            // Check cache (15 minutes)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Build Open-Meteo API URL for current weather
            $url = add_query_arg( [
                'latitude' => round( $lat, 4 ),
                'longitude' => round( $lon, 4 ),
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,cloud_cover,pressure_msl,surface_pressure,wind_speed_10m,wind_direction_10m,wind_gusts_10m',
                'daily' => 'sunrise,sunset',
                'temperature_unit' => 'fahrenheit',
                'wind_speed_unit' => 'mph',
                'precipitation_unit' => 'inch',
                'timezone' => 'America/New_York',
                'forecast_days' => 1,
            ], self::OPEN_METEO_API );

            $response = $this->make_api_request( $url, false );

            if( ! $response || ! isset( $response['current'] ) ) {
                return false;
            }

            $current = $response['current'];
            $daily = $response['daily'] ?? [];

            // Get location name
            $location_info = $this->reverse_geocode( $lat, $lon );

            // Convert to object matching expected format
            $weather = (object) [
                'main' => (object) [
                    'temp' => round( $current['temperature_2m'] ?? 0 ),
                    'feels_like' => round( $current['apparent_temperature'] ?? $current['temperature_2m'] ?? 0 ),
                    'humidity' => $current['relative_humidity_2m'] ?? 0,
                    'pressure' => round( $current['pressure_msl'] ?? $current['surface_pressure'] ?? 0 ),
                ],
                'weather' => [
                    (object) [
                        'description' => SGU_Static::wmo_code_to_description( $current['weather_code'] ?? 0 ),
                        'icon' => SGU_Static::wmo_code_to_icon( $current['weather_code'] ?? 0, $current['is_day'] ?? 1 ),
                    ]
                ],
                'wind' => (object) [
                    'speed' => round( $current['wind_speed_10m'] ?? 0 ),
                    'deg' => $current['wind_direction_10m'] ?? 0,
                    'gust' => round( $current['wind_gusts_10m'] ?? 0 ),
                ],
                'visibility' => null,
                'clouds' => (object) [
                    'all' => $current['cloud_cover'] ?? 0,
                ],
                'dt' => strtotime( $current['time'] ?? 'now' ),
                'sys' => (object) [
                    'sunrise' => isset( $daily['sunrise'][0] ) ? strtotime( $daily['sunrise'][0] ) : null,
                    'sunset' => isset( $daily['sunset'][0] ) ? strtotime( $daily['sunset'][0] ) : null,
                ],
                'name' => $location_info->name ?? '',
                'state' => $location_info->state ?? '',
            ];

            // Cache for 15 minutes
            wp_cache_set( $cache_key, $weather, 'sgu_weather', 15 * MINUTE_IN_SECONDS );

            return $weather;
        }

        /** 
         * get_hourly_forecast
         * 
         * Get hourly forecast from Open-Meteo API
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return object|bool Forecast data object or false on failure
         * 
        */
        public function get_hourly_forecast( float $lat, float $lon ) : object|bool {

            // Build cache key
            $cache_key = sprintf( 'sgu_openmeteo_hourly_%s_%s', round( $lat, 2 ), round( $lon, 2 ) );
            
            // Check cache (1 hour)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Build Open-Meteo API URL for hourly forecast
            $url = add_query_arg( [
                'latitude' => round( $lat, 4 ),
                'longitude' => round( $lon, 4 ),
                'hourly' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation_probability,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,is_day',
                'temperature_unit' => 'fahrenheit',
                'wind_speed_unit' => 'mph',
                'precipitation_unit' => 'inch',
                'timezone' => 'America/New_York',
                'forecast_days' => 3,
            ], self::OPEN_METEO_API );

            $response = $this->make_api_request( $url, false );

            if( ! $response || ! isset( $response['hourly'] ) ) {
                return false;
            }

            $hourly_data = $response['hourly'];
            $hourly = [];

            // Convert to expected format
            $count = count( $hourly_data['time'] ?? [] );
            for( $i = 0; $i < $count; $i++ ) {
                $hourly[] = [
                    'dt' => strtotime( $hourly_data['time'][$i] ),
                    'temp' => round( $hourly_data['temperature_2m'][$i] ?? 0 ),
                    'feels_like' => round( $hourly_data['apparent_temperature'][$i] ?? 0 ),
                    'humidity' => $hourly_data['relative_humidity_2m'][$i] ?? 0,
                    'weather' => [
                        [
                            'description' => SGU_Static::wmo_code_to_description( $hourly_data['weather_code'][$i] ?? 0 ),
                            'icon' => SGU_Static::wmo_code_to_icon( $hourly_data['weather_code'][$i] ?? 0, $hourly_data['is_day'][$i] ?? 1 ),
                        ]
                    ],
                    'pop' => ( $hourly_data['precipitation_probability'][$i] ?? 0 ) / 100,
                    'precipitation' => $hourly_data['precipitation'][$i] ?? 0,
                    'clouds' => $hourly_data['cloud_cover'][$i] ?? 0,
                    'wind_speed' => round( $hourly_data['wind_speed_10m'][$i] ?? 0 ),
                    'wind_deg' => $hourly_data['wind_direction_10m'][$i] ?? 0,
                ];
            }

            $forecast = (object) [
                'hourly' => $hourly,
                'daily' => [],
            ];

            // Cache for 1 hour
            wp_cache_set( $cache_key, $forecast, 'sgu_weather', HOUR_IN_SECONDS );

            return $forecast;
        }

        /** 
         * get_daily_forecast
         * 
         * Get daily forecast from Open-Meteo API
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return object|bool Forecast data object or false on failure
         * 
        */
        public function get_daily_forecast( float $lat, float $lon ) : object|bool {

            // Build cache key
            $cache_key = sprintf( 'sgu_openmeteo_daily_%s_%s', round( $lat, 2 ), round( $lon, 2 ) );
            
            // Check cache (3 hours)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Build Open-Meteo API URL for daily forecast
            $url = add_query_arg( [
                'latitude' => round( $lat, 4 ),
                'longitude' => round( $lon, 4 ),
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,sunrise,sunset,uv_index_max,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_direction_10m_dominant',
                'temperature_unit' => 'fahrenheit',
                'wind_speed_unit' => 'mph',
                'precipitation_unit' => 'inch',
                'timezone' => 'America/New_York',
                'forecast_days' => 8,
            ], self::OPEN_METEO_API );

            $response = $this->make_api_request( $url, false );

            if( ! $response || ! isset( $response['daily'] ) ) {
                return false;
            }

            $daily_data = $response['daily'];
            $daily = [];

            // Convert to expected format
            $count = count( $daily_data['time'] ?? [] );
            for( $i = 0; $i < $count; $i++ ) {
                $daily[] = [
                    'dt' => strtotime( $daily_data['time'][$i] ),
                    'temp' => [
                        'max' => round( $daily_data['temperature_2m_max'][$i] ?? 0 ),
                        'min' => round( $daily_data['temperature_2m_min'][$i] ?? 0 ),
                    ],
                    'feels_like' => [
                        'max' => round( $daily_data['apparent_temperature_max'][$i] ?? 0 ),
                        'min' => round( $daily_data['apparent_temperature_min'][$i] ?? 0 ),
                    ],
                    'weather' => [
                        [
                            'description' => SGU_Static::wmo_code_to_description( $daily_data['weather_code'][$i] ?? 0 ),
                            'icon' => SGU_Static::wmo_code_to_icon( $daily_data['weather_code'][$i] ?? 0, 1 ),
                        ]
                    ],
                    'pop' => ( $daily_data['precipitation_probability_max'][$i] ?? 0 ) / 100,
                    'precipitation' => $daily_data['precipitation_sum'][$i] ?? 0,
                    'uvi' => $daily_data['uv_index_max'][$i] ?? 0,
                    'sunrise' => strtotime( $daily_data['sunrise'][$i] ?? 'now' ),
                    'sunset' => strtotime( $daily_data['sunset'][$i] ?? 'now' ),
                    'wind_speed' => round( $daily_data['wind_speed_10m_max'][$i] ?? 0 ),
                    'wind_deg' => $daily_data['wind_direction_10m_dominant'][$i] ?? 0,
                    'summary' => SGU_Static::wmo_code_to_description( $daily_data['weather_code'][$i] ?? 0 ),
                ];
            }

            $forecast = (object) [
                'daily' => $daily,
                'hourly' => [],
            ];

            // Cache for 3 hours
            wp_cache_set( $cache_key, $forecast, 'sgu_weather', 3 * HOUR_IN_SECONDS );

            return $forecast;
        }

        /** 
         * get_noaa_forecast
         * 
         * Get detailed forecast from NOAA Weather API
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return object|bool NOAA forecast data or false on failure
         * 
        */
        public function get_noaa_forecast( float $lat, float $lon ) : object|bool {

            // Build cache key
            $cache_key = sprintf( 'sgu_noaa_forecast_%s_%s', round( $lat, 4 ), round( $lon, 4 ) );
            
            // Check cache (1 hour)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Get grid point info
            $grid = $this->get_grid_point( $lat, $lon );
            if( ! $grid ) {
                return false;
            }

            // Get forecast URL
            $forecast_url = $grid['forecast'] ?? null;
            if( ! $forecast_url ) {
                return false;
            }

            $forecast_response = $this->make_api_request( $forecast_url, true );
            if( ! $forecast_response || ! isset( $forecast_response['properties'] ) ) {
                return false;
            }

            // Build forecast object
            $forecast = (object) [
                'location' => (object) [
                    'city' => $grid['location']['city'] ?? '',
                    'state' => $grid['location']['state'] ?? '',
                    'gridId' => $grid['gridId'] ?? '',
                    'gridX' => $grid['gridX'] ?? '',
                    'gridY' => $grid['gridY'] ?? '',
                ],
                'periods' => $forecast_response['properties']['periods'] ?? [],
                'generatedAt' => $forecast_response['properties']['generatedAt'] ?? '',
                'updateTime' => $forecast_response['properties']['updateTime'] ?? '',
            ];

            // Cache for 1 hour
            wp_cache_set( $cache_key, $forecast, 'sgu_weather', HOUR_IN_SECONDS );

            return $forecast;
        }

        /** 
         * get_noaa_alerts
         * 
         * Get active weather alerts from NOAA for a location
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return array Array of active alerts
         * 
        */
        public function get_noaa_alerts( float $lat, float $lon ) : array {

            // Build cache key
            $cache_key = sprintf( 'sgu_noaa_alerts_%s_%s', round( $lat, 2 ), round( $lon, 2 ) );
            
            // Check cache (15 minutes)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Build the alerts URL
            $url = sprintf(
                'https://api.weather.gov/alerts/active?point=%s,%s',
                round( $lat, 4 ),
                round( $lon, 4 )
            );

            $response = $this->make_api_request( $url, true );

            if( ! $response || ! isset( $response['features'] ) ) {
                return [];
            }

            // Extract alert information
            $alerts = [];
            foreach( $response['features'] as $feature ) {
                $props = $feature['properties'] ?? [];
                $alerts[] = (object) [
                    'event' => $props['event'] ?? '',
                    'severity' => $props['severity'] ?? '',
                    'urgency' => $props['urgency'] ?? '',
                    'headline' => $props['headline'] ?? '',
                    'description' => $props['description'] ?? '',
                    'instruction' => $props['instruction'] ?? '',
                    'effective' => $props['effective'] ?? '',
                    'expires' => $props['expires'] ?? '',
                    'senderName' => $props['senderName'] ?? '',
                ];
            }

            // Cache for 15 minutes
            wp_cache_set( $cache_key, $alerts, 'sgu_weather', 15 * MINUTE_IN_SECONDS );

            return $alerts;
        }

        /** 
         * geocode_zip
         * 
         * Convert a ZIP code to latitude/longitude coordinates
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $zip US ZIP code
         * 
         * @return object|bool Object with lat/lon or false on failure
         * 
        */
        public function geocode_zip( string $zip ) : object|bool {

            // Sanitize ZIP code
            $zip = preg_replace( '/[^0-9]/', '', $zip );
            
            // Validate ZIP code format (5 digits)
            if( strlen( $zip ) !== 5 ) {
                return false;
            }

            // Build cache key
            $cache_key = sprintf( 'sgu_geocode_zip_%s', $zip );
            
            // Check cache (30 days)
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Use Zippopotam.us API (free, no key required, reliable for ZIP codes)
            $url = sprintf( 'https://api.zippopotam.us/us/%s', $zip );

            $response = $this->make_api_request( $url, false );

            if( ! $response || ! isset( $response['places'][0] ) ) {
                return false;
            }

            $place = $response['places'][0];

            if( empty( $place['latitude'] ) || empty( $place['longitude'] ) ) {
                return false;
            }

            // Build location object
            $location = (object) [
                'lat' => (float) $place['latitude'],
                'lon' => (float) $place['longitude'],
                'name' => $place['place name'] ?? $zip,
                'state' => $place['state abbreviation'] ?? '',
                'zip' => $zip,
                'country' => 'US',
            ];

            // Cache for 30 days
            wp_cache_set( $cache_key, $location, 'sgu_weather', 30 * DAY_IN_SECONDS );

            return $location;
        }
        
        /** 
         * reverse_geocode
         * 
         * Convert latitude/longitude to location name using Open-Meteo Geocoding
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return object|bool Location object or false on failure
         * 
        */
        public function reverse_geocode( float $lat, float $lon ) : object|bool {

            // Build cache key
            $cache_key = sprintf( 'sgu_reverse_geocode_%s_%s', round( $lat, 4 ), round( $lon, 4 ) );
            
            // Check cache
            $cached = wp_cache_get( $cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                return $cached;
            }

            // Try NOAA first for US locations (more accurate for US)
            $grid = $this->get_grid_point( $lat, $lon );
            if( $grid && ! empty( $grid['location']['city'] ) ) {
                $location = (object) [
                    'lat' => (float) $lat,
                    'lon' => (float) $lon,
                    'name' => $grid['location']['city'],
                    'state' => $grid['location']['state'] ?? '',
                    'country' => 'US',
                ];

                wp_cache_set( $cache_key, $location, 'sgu_weather', 30 * DAY_IN_SECONDS );
                return $location;
            }

            // Fallback: use Open-Meteo geocoding search with coordinates
            // Since Open-Meteo doesn't have reverse geocoding, we'll use a simple approach
            $location = (object) [
                'lat' => (float) $lat,
                'lon' => (float) $lon,
                'name' => sprintf( '%.2f, %.2f', $lat, $lon ),
                'state' => '',
                'country' => '',
            ];

            wp_cache_set( $cache_key, $location, 'sgu_weather', DAY_IN_SECONDS );

            return $location;
        }

        /** 
         * get_grid_point
         * 
         * Get NOAA grid point data for coordinates (cached)
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param float $lat Latitude
         * @param float $lon Longitude
         * 
         * @return array|bool Grid point data or false on failure
         * 
        */
        private function get_grid_point( float $lat, float $lon ) : array|bool {

            // Build cache key
            $cache_key = sprintf( '%s_%s', round( $lat, 4 ), round( $lon, 4 ) );

            // Check in-memory cache
            if( isset( $this->grid_cache[ $cache_key ] ) ) {
                return $this->grid_cache[ $cache_key ];
            }

            // Check WP cache (grid points don't change, cache for 7 days)
            $wp_cache_key = sprintf( 'sgu_noaa_grid_%s', $cache_key );
            $cached = wp_cache_get( $wp_cache_key, 'sgu_weather' );
            if( $cached !== false ) {
                $this->grid_cache[ $cache_key ] = $cached;
                return $cached;
            }

            // Get the grid point from NOAA
            $points_url = sprintf(
                'https://api.weather.gov/points/%s,%s',
                round( $lat, 4 ),
                round( $lon, 4 )
            );

            $response = $this->make_api_request( $points_url, true );

            if( ! $response || ! isset( $response['properties'] ) ) {
                return false;
            }

            $props = $response['properties'];

            // Build grid data array
            $grid = [
                'gridId' => $props['gridId'] ?? '',
                'gridX' => $props['gridX'] ?? '',
                'gridY' => $props['gridY'] ?? '',
                'forecast' => $props['forecast'] ?? null,
                'forecastHourly' => $props['forecastHourly'] ?? null,
                'observationStations' => $props['observationStations'] ?? null,
                'location' => [
                    'city' => $props['relativeLocation']['properties']['city'] ?? '',
                    'state' => $props['relativeLocation']['properties']['state'] ?? '',
                ],
            ];

            // Cache in memory and WP
            $this->grid_cache[ $cache_key ] = $grid;
            wp_cache_set( $wp_cache_key, $grid, 'sgu_weather', 7 * DAY_IN_SECONDS );

            return $grid;
        }

        /** 
         * make_api_request
         * 
         * Execute HTTP request to external API
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $url Full URL to request
         * @param bool $is_noaa Whether this is a NOAA request (default false)
         * 
         * @return array|bool Parsed response or false on failure
         * 
        */
        private function make_api_request( string $url, bool $is_noaa = false ) : array|bool {

            // Configure HTTP request parameters
            $args = [
                'timeout' => 30,
                'redirection' => 3,
            ];

            // NOAA requires specific headers
            if( $is_noaa ) {
                $args['headers'] = [
                    'Accept' => 'application/geo+json',
                    'User-Agent' => '(US Star Gazers, iam@kevinpirnie.com)',
                ];
            } else {
                $args['user-agent'] = 'US Star Gazers Weather ( iam@kevinpirnie.com )';
            }

            // make our request and return the response
            $resp = SGU_Static::get_remote_data( $url, $args['headers'] ?? [] );
            return $resp['body'] ?: [];
        }

    }

}
<?php
/**
 * Astronomy API Data Class
 * 
 * Handles API calls to AstronomyAPI.com for:
 * - Planetary Positions
 * - Star Chart (area type)
 * - Moon Phase
 * 
 * @package US Star Gazers
 * @since 8.4
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

if ( ! class_exists( 'SGU_Space_API' ) ) :

    class SGU_Space_API {

        private string $api_base;

        private ?string $app_id = null;

        private ?string $app_secret = null;

        private const CACHE_DURATION = 3600;

        public function __construct() {

            $this -> api_base = SGU_Static::get_sgu_option('sgu_api_settings') -> sgup_aapi_endpoint ?? '';

            // get the key strings we need
            $the_items = SGU_Static::get_api_key( 'aapi' );
            if($the_items) {
                $the_item = explode( '|', $the_items[array_rand( $the_items, 1 )]['key'] );
            }
            // set the api's IDs and Secrets
            $this->app_id     = $the_item[0] ?: '';
            $this->app_secret = $the_item[1] ?: '';
        }

        public function init( ) {}

        public function has_credentials(): bool {
            return ! empty( $this->app_id ) && ! empty( $this->app_secret );
        }

        private function get_auth_header(): string {
            return 'Basic ' . base64_encode( $this->app_id . ':' . $this->app_secret );
        }

        private function request( string $endpoint, array $body = [], string $method = 'GET' ): ?object {
            if ( ! $this->has_credentials() ) {
                return null;
            }

            $url = $this->api_base . $endpoint;

            $args = [
                'method'  => $method,
                'timeout' => 30,
                'headers' => [
                    'Authorization' => $this->get_auth_header(),
                    'Content-Type'  => 'application/json',
                ],
            ];

            if ( $method === 'POST' && ! empty( $body ) ) {
                $args['body'] = wp_json_encode( $body );
            } elseif ( $method === 'GET' && ! empty( $body ) ) {
                $url = add_query_arg( $body, $url );
            }

            $response = wp_remote_request( $url, $args );

            if ( is_wp_error( $response ) ) {
                error_log( 'SGU Astronomy API Error: ' . $response->get_error_message() );
                return null;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                error_log( 'SGU Astronomy API Error: HTTP ' . $code );
                return null;
            }

            $body = wp_remote_retrieve_body( $response );
            return json_decode( $body );
        }

        public function get_planet_positions( float $latitude, float $longitude, string $date = '' ): ?object {
            if ( empty( $date ) ) {
                $date = current_time( 'Y-m-d' );
            }

            $cache_key = 'sgu_planets_' . md5( $latitude . $longitude . $date );
            $cached    = get_transient( $cache_key );

            if ( $cached !== false ) {
                return $cached;
            }

            $params = [
                'latitude'  => $latitude,
                'longitude' => $longitude,
                'elevation' => 0,
                'from_date' => $date,
                'to_date'   => $date,
                'time'      => current_time( 'H:i:s' ),
            ];

            $result = $this->request( '/bodies/positions', $params, 'GET' );

            if ( $result ) {
                set_transient( $cache_key, $result, self::CACHE_DURATION );
            }

            return $result;
        }

        public function get_moon_phase( 
            float $latitude, 
            float $longitude, 
            string $date = '',
            string $style = 'shaded',
            string $format = 'png'
        ): ?string {
            if ( empty( $date ) ) {
                $date = current_time( 'Y-m-d' );
            }

            $cache_key = 'sgu_moonphase_' . md5( $latitude . $longitude . $date . $style );
            $cached    = get_transient( $cache_key );

            if ( $cached !== false ) {
                return $cached;
            }

            $body = [
                'format'   => $format,
                'style'    => [
                    'moonStyle'       => $style,
                    'backgroundStyle' => 'stars',
                    'backgroundColor' => '#000000',
                    'headingColor'    => '#ffffff',
                    'textColor'       => '#ffffff',
                ],
                'observer' => [
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                    'date'      => $date,
                ],
                'view'     => [
                    'type'        => 'portrait-simple',
                    'orientation' => 'north-up',
                ],
            ];

            $result = $this->request( '/studio/moon-phase', $body, 'POST' );

            if ( $result && isset( $result->data->imageUrl ) ) {
                set_transient( $cache_key, $result->data->imageUrl, self::CACHE_DURATION );
                return $result->data->imageUrl;
            }

            return null;
        }

    }

endif;
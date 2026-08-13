<?php
/**
 * USNO API Data Class
 * 
 * Handles API calls to US Naval Observatory for:
 * - Sun rise/set times
 * - Moon rise/set times
 * - Moon phase data
 * - Civil twilight times
 * 
 * @package US Star Gazers
 * @since 8.4
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

if ( ! class_exists( 'SGU_USNO_API' ) ) :

    class SGU_USNO_API {

        private const API_BASE = 'https://aa.usno.navy.mil/api';

        private const API_ID = 'SGUStars';

        private const CACHE_DURATION = 21600;

        public function init( ) {}

        public function get_rise_set_data( 
            float $latitude, 
            float $longitude, 
            string $date = '',
            ?int $tz = null,
            bool $dst = false 
        ): ?object {
            if ( empty( $date ) ) {
                $date = current_time( 'Y-m-d' );
            }

            if ( $tz === null ) {
                $tz = (int) get_option( 'gmt_offset', 0 );
            }

            $cache_key = 'sgu_usno_' . md5( $latitude . $longitude . $date . $tz . ( $dst ? '1' : '0' ) );
            $cached    = get_transient( $cache_key );

            if ( $cached !== false ) {
                return $cached;
            }

            $url = self::API_BASE . '/rstt/oneday';

            $params = [
                'date'   => $date,
                'coords' => round( $latitude, 4 ) . ',' . round( $longitude, 4 ),
                'tz'     => $tz,
                'dst'    => $dst ? 'true' : 'false',
                'ID'     => self::API_ID,
            ];

            $url = add_query_arg( $params, $url );

            $response = wp_remote_get( $url, [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ] );

            if ( is_wp_error( $response ) ) {
                error_log( 'SGU USNO API Error: ' . $response->get_error_message() );
                return null;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                error_log( 'SGU USNO API Error: HTTP ' . $code );
                return null;
            }

            $body   = wp_remote_retrieve_body( $response );
            $result = json_decode( $body );

            if ( $result && isset( $result->properties->data ) ) {
                $normalized = $this->normalize_data( $result->properties->data );
                set_transient( $cache_key, $normalized, self::CACHE_DURATION );
                return $normalized;
            }

            return null;
        }

        private function normalize_data( object $data ): object {
            return (object) [
                'date'        => sprintf( '%04d-%02d-%02d', $data->year, $data->month, $data->day ),
                'day_of_week' => $data->day_of_week ?? '',
                'timezone'    => $data->tz ?? 0,
                'is_dst'      => $data->isdst ?? false,
                'sun'         => $this->parse_phenomena( $data->sundata ?? [] ),
                'moon'        => $this->parse_phenomena( $data->moondata ?? [] ),
                'moon_phase'  => (object) [
                    'current'      => $data->curphase ?? '',
                    'illumination' => $data->fracillum ?? '',
                    'closest'      => isset( $data->closestphase ) ? (object) [
                        'phase' => $data->closestphase->phase ?? '',
                        'date'  => isset( $data->closestphase->year ) 
                            ? sprintf( '%04d-%02d-%02d', $data->closestphase->year, $data->closestphase->month, $data->closestphase->day )
                            : '',
                        'time'  => $data->closestphase->time ?? '',
                    ] : null,
                ],
            ];
        }

        private function parse_phenomena( array $phenomena ): object {
            $result = (object) [
                'rise'                 => null,
                'set'                  => null,
                'transit'              => null,
                'civil_twilight_begin' => null,
                'civil_twilight_end'   => null,
            ];

            foreach ( $phenomena as $item ) {
                $phen = strtolower( $item->phen ?? '' );
                $time = $item->time ?? null;

                if ( strpos( $phen, 'rise' ) !== false ) {
                    $result->rise = $time;
                } elseif ( strpos( $phen, 'set' ) !== false ) {
                    $result->set = $time;
                } elseif ( strpos( $phen, 'transit' ) !== false ) {
                    $result->transit = $time;
                } elseif ( strpos( $phen, 'begin civil' ) !== false ) {
                    $result->civil_twilight_begin = $time;
                } elseif ( strpos( $phen, 'end civil' ) !== false ) {
                    $result->civil_twilight_end = $time;
                }
            }

            return $result;
        }

    }

endif;
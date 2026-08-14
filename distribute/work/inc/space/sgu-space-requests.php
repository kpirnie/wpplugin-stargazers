<?php
/** 
 * Space Requests
 * 
 * This file contains the space API request methods for fetching astronomy data
 * from external APIs (NASA DONKI, NOAA SWPC, etc.). Handles API authentication,
 * request construction, response parsing, and delegates data storage to SGU_Space_Data.
 * 
 * @since 8.0
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// make sure this class does not already exist
if( ! class_exists( 'SGU_Space_Requests' ) ) {

    /** 
     * Class SGU_Space_Requests
     * 
     * Manages API requests to external space weather and astronomy services.
     * Coordinates between API endpoints, authentication keys, and data storage.
     * Implements caching for API keys and endpoints to reduce repeated option queries.
     * 
     * Key responsibilities:
     * - Fetch data from NASA and NOAA APIs
     * - Manage API key rotation and endpoint configuration
     * - Parse JSON and plain text API responses
     * - Delegate data storage to SGU_Space_Data_CRUD class
     * 
     * @since 8.0
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers
     * 
    */
    class SGU_Space_Requests {

        /** 
         * @var SGU_Space_Data_CRUD|null Database handler instance for storing fetched data
         */
        private ?SGU_Space_Data_CRUD $space_data;
        
        /** 
         * __construct
         * 
         * Initialize the space requests handler.
         * Creates the SGU_Space_Data_CRUD instance that will handle all database operations.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
        */
        public function __construct( ) {

            // Initialize data handler for storing API responses
            $this -> space_data = new SGU_Space_Data_CRUD( );
        }

        /** 
         * process_sync_data
         * 
         * Main entry point for fetching and storing data from external APIs.
         * Coordinates the complete workflow: fetch from API, parse response,
         * and delegate to appropriate storage method.
         * 
         * Workflow:
         * 1. Fetch raw data from API via get_the_data()
         * 2. Match data type to appropriate insert method
         * 3. Return success/failure status
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param string $which Data type identifier (geo, neo, sf, sw, apod, cme)
         * 
         * @return bool True if data successfully stored, false on failure
         * 
        */
        public function process_sync_data( string $which ) : bool {
            
            // Fetch raw data from appropriate API
            $data = $this -> get_the_data( $which );

            // Route data to correct storage method based on type
            return match( $which ) {
                'geo' => $this -> space_data -> insert_geo( $data ),           // Geomagnetic forecasts
                'neo' => $this -> space_data -> insert_neo( $data ),           // Near Earth Objects
                'sf' => $this -> space_data -> insert_solar_flare( $data ),    // Solar flares
                'sw' => $this -> space_data -> insert_space_weather( $data ),  // Space weather alerts
                'apod' => $this -> space_data -> insert_apod( $data ),         // Astronomy Photo of the Day
                'cme' => $this -> space_data -> insert_cme( $data ),           // Coronal Mass Ejections
    
                default => true,                                                // Unknown type - skip silently
            };
        }

        /** 
         * get_the_data
         * 
         * Fetches raw data from external API endpoints.
         * Handles API key rotation and constructs proper request URLs.
         * 
         * Process:
         * 1. Load API keys for this data type
         * 2. Load configured endpoints
         * 3. For each endpoint:
         *    - Select random API key (for rate limit distribution)
         *    - Format URL with key
         *    - Make HTTP request
         * 4. Return first successful response
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $which Data type identifier (geo, neo, sf, sw, apod, cme)
         * 
         * @return array Parsed API response data, or empty array on failure
         * 
        */
        private function get_the_data( string $which ) : array {
            
            // Load API keys (cached after first call)
            $keys = SGU_Static::get_api_key($which);
            
            // Load endpoints (cached after first call)
            $endpoints = SGU_Static::get_api_endpoint($which);

            // Array to store responses
            $ret = [];
        
            // Loop through configured endpoints (usually just one)
            foreach( $endpoints as $endpoint ) {
                
                // Select random API key if keys are required for this endpoint
                // Some endpoints (NOAA) don't require keys, others (NASA) do
                $key = ( in_array( $which, ['cme', 'sf', 'neo', 'apod'] ) ) 
                    ? $keys[ array_rand( $keys, 1 ) ]['key']  // Random key from pool
                    : '';                               // No key needed
                
                // Format endpoint URL with API key
                // Endpoint should contain %s placeholder for key
                $url = sprintf( $endpoint, $key );

                // get a response
                $resp = SGU_Static::get_remote_data( $url );
                
                // Make HTTP request and parse response
                $ret[] = ($resp['success']) ? $resp['body'] : [];

            }

            // check if we're syncing the apod
            if( $which === 'apod' ){
                // Check if API response is empty or failed
                $has_data = false;
                foreach( $ret as $r ) {
                    if( ! empty( $r ) ) {
                        $has_data = true;
                        break;
                    }
                }
                
                // If no data from API, try to scrape
                if( ! $has_data ) {
                    $ss = new SGU_Sync( );
                    $scraped = $ss -> scrape_apod_archive( date( 'Y-m-d' ), date( 'Y-m-d' ) );
                    if( ! empty( $scraped ) ) {
                        $ret = [ $scraped ];
                    }
                }
            }

            // Return first response (or empty array if all failed)
            return $ret;
        }

    }
}
<?php
/** 
 * Space Data
 * 
 * This file contains the space data methods for inserting/updating
 * astronomy-related custom post types. Handles all CRUD operations
 * for CME alerts, solar flares, NEOs, APOD, and other space data.
 * 
 * @since 8.0
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// make sure this class does not already exist
if( ! class_exists( 'SGU_Space_Data_CRUD' ) ) {

    /** 
     * Class SGU_Space_Data_CRUD
     * 
     * The actual class running the space data operations.
     * Provides unified methods for querying custom post types with caching,
     * and handles insertion/updating of space-related content from APIs.
     * 
     * @since 8.0
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers
     * 
    */
    class SGU_Space_Data_CRUD {

        /** 
         * insert_geo
         * 
         * Processes and inserts geomagnetic storm forecast data from NOAA.
         * Parses plain text format data to extract product name and issue date.
         * Only inserts new posts - existing posts are not updated.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data Array containing plain text forecast data from NOAA API
         * 
         * @return bool True if successfully inserted, false if data empty or insert failed
         * 
        */
        public function insert_geo( array $data ) : bool {
            
            // Early return if no data provided
            if( ! $data ) { 
                return false; 
            }

            // Extract product name from text using regex
            // Example: ":Product: 3-Day Forecast"
            preg_match( '/:Product:\ (.*)/', $data[0], $match );
            $product = ( $match[1] ) ?? '3-Day Forecast';
            
            // Extract issue date from text
            // Example: ":Issued: 2024 Dec 11 0030 UTC"
            preg_match( '/:Issued:\ (.*)/', $data[0], $match );
            $issued = ( SGU_Static::parse_alert_date( $match[1] ) ) ?? '';
            
            // Construct post title from product and date
            $title = $product . ' - ' . $issued;
            
            // Check if post already exists by slug
            $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $title ), 'sgu_geo_alerts' ) ) ?: 0;

            // Only insert if post doesn't exist
            if( $existing_id == 0 ) {
                
                // Build post data array
                $args = [
                    'post_status' => 'publish',
                    'post_title' => sanitize_text_field( $title ),
                    'post_content' => maybe_serialize( $data[0] ),  // Store full text as serialized data
                    'post_type' => 'sgu_geo_alerts',
                    'post_author' => 16,                            // System user ID
                    'post_date' => sanitize_text_field( $issued ),
                ];

                // Insert the new post
                $existing_id = wp_insert_post( $args );
            }

            // Convert post ID to boolean (0 = false, any other number = true)
            return filter_var( $existing_id, FILTER_VALIDATE_BOOLEAN );
        }

        /** 
         * insert_neo
         * 
         * Processes and inserts Near Earth Object data from NASA's NEO API.
         * Handles nested data structure where NEOs are grouped by date.
         * Stores complete object data as serialized post content.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data Array containing NEO feed data from NASA API
         * 
         * @return bool Always returns true (bulk operation doesn't track individual failures)
         * 
        */
        public function insert_neo( array $the_data ) : bool {
            
            // Early return if no data
            if( ! $the_data ) { 
                return false; 
            }

            // the actuall data
            $data = $the_data[0];

            // Extract the nested NEO data grouped by date
            $neos = $data['near_earth_objects'];

            // Loop through each date in the feed
            foreach( $neos as $key => $val ) {
                
                // The key is the approach date
                $date = $key;

                // Loop through each NEO on this date
                foreach( $val as $object ) {
                    
                    // Clean up NEO name by removing parentheses
                    // Example: "(2024 AB1)" becomes "2024 AB1"
                    $name = str_replace( ['(', ')'], '', $object['name'] );
                    
                    // Extract hazardous status as boolean
                    $hazardous = filter_var( $object['is_potentially_hazardous_asteroid'], FILTER_VALIDATE_BOOLEAN );
                    
                    // Format post date from approach date
                    $posted = date( 'Y-m-d', strtotime( $date ?: date( "Y-m-d" ) ) );

                    // Check if this NEO already exists by name
                    $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $name ), 'sgu_neo' ) ) ?: 0;

                    // Only insert if new
                    if( $existing_id == 0 ) {
                        
                        // Build post data
                        $args = [
                            'post_status' => 'publish',
                            'post_date' => sanitize_text_field( $posted  . ' 00:00 UTC' ),
                            'post_title' => sanitize_text_field( $name ),
                            'post_content' => maybe_serialize( $object ),  // Store complete NEO data
                            'post_type' => 'sgu_neo',
                            'post_author' => 16,
                        ];

                        // Insert post and get new ID
                        $existing_id = wp_insert_post( $args );
                        
                        // Store hazardous status as post meta for easier querying
                        update_post_meta( $existing_id, 'sgu_neo_hazardous', $hazardous );
                    }
                }
            }

            // Always return true for bulk operations
            return true;
        }

        /** 
         * insert_solar_flare
         * 
         * Processes and inserts solar flare event data from NASA's DONKI API.
         * Updates existing posts if NASA revises the data (common with flares).
         * Each flare is uniquely identified by its flrID from NASA.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data Array of solar flare objects from NASA DONKI API
         * 
         * @return bool True if processing succeeded, false if no data
         * 
        */
        public function insert_solar_flare( array $data ) : bool {
            
            // Early return if no data
            if( ! $data ) { 
                return false; 
            }

            // Loop through each flare in the feed
            foreach( $data[0] as $flare ) {
                
                // Extract unique flare ID from NASA
                $title = sanitize_text_field( $flare['flrID'] );
                
                // Parse begin time to WordPress format
                $date = sanitize_text_field( date( 'Y-m-d H:i:s', strtotime( $flare['beginTime'] ) ) );

                // Check if this flare already exists
                $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $title ), 'sgu_sf_alerts' ) ) ?: 0;

                // Build post data
                $args = [
                    'post_status' => 'publish',
                    'post_title' => $title,
                    'post_content' => maybe_serialize( $flare ),  // Store complete flare data
                    'post_type' => 'sgu_sf_alerts',
                    'post_author' => 16,
                    'post_date' => $date,
                ];

                // Insert new post or update existing
                // NASA sometimes revises flare classifications
                if( $existing_id == 0 ) {
                    wp_insert_post( $args );
                } else {
                    // Update existing post with revised data
                    $args['ID'] = $existing_id;
                    wp_update_post( $args );
                }
            }

            return true;
        }

        /** 
         * insert_space_weather
         * 
         * Processes and inserts space weather alert messages from NOAA.
         * These are official notifications from the Space Weather Prediction Center.
         * Updates existing alerts if NOAA revises them.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data Array of space weather alert objects from NOAA API
         * 
         * @return bool True if processing succeeded, false if no data
         * 
        */
        public function insert_space_weather( array $data ) : bool {
            
            // Early return if no data
            if( ! $data ) { 
                return false; 
            }

            // Loop through each alert
            foreach( $data[0] as $object ) {
                
                // Parse issue datetime to WordPress format
                $date = sanitize_text_field( date( 'Y-m-d H:i:s', strtotime( $object['issue_datetime'] ) ) );
                
                // Build unique title from product ID and issue time
                // Example: "ALTK05 2024-12-11 14:30:00"
                $title = sanitize_text_field( $object['product_id'] . ' ' . $date );

                // Check if alert already exists
                $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $title ), 'sgu_sw_alerts' ) ) ?: 0;

                // Build post data
                $args = [
                    'post_status' => 'publish',
                    'post_title' => $title,
                    'post_content' => maybe_serialize( $object ),  // Store complete alert data
                    'post_type' => 'sgu_sw_alerts',
                    'post_author' => 16,
                    'post_date' => $date,
                ];

                // Insert new or update existing
                if( $existing_id == 0 ) {
                    wp_insert_post( $args );
                } else {
                    // Update existing with revised alert
                    $args['ID'] = $existing_id;
                    wp_update_post( $args );
                }
            }

            return true;
        }

        /** 
         * insert_cme
         * 
         * Processes and inserts Coronal Mass Ejection data from NASA's DONKI API.
         * CMEs are identified by their activityID from NASA.
         * Updates existing CMEs if NASA revises the analysis.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data Array of CME objects from NASA DONKI API
         * 
         * @return bool True if processing succeeded, false if no data
         * 
        */
        public function insert_cme( array $data ) : bool {
            
            // Early return if no data
            if( ! $data ) { 
                return false; 
            }

            // Loop through each CME event
            foreach( $data[0] as $object ) {
                
                // Parse start time to WordPress format
                $date = sanitize_text_field( date( 'Y-m-d H:i:s', strtotime( $object['startTime'] ) ) );
                
                // Get unique activity ID from NASA
                $title = sanitize_text_field( $object['activityID'] );
                
                // Serialize complete CME data for storage
                $content = maybe_serialize( $object );

                // Check if CME already exists
                $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $title ), 'sgu_cme_alerts' ) ) ?: 0;

                // Build post data
                $args = [
                    'post_status' => 'publish',
                    'post_title' => $title,
                    'post_content' => $content,
                    'post_type' => 'sgu_cme_alerts',
                    'post_author' => 16,
                    'post_date' => $date,
                ];

                // Insert new or update existing
                // NASA refines CME analysis as more data comes in
                if( $existing_id == 0 ) {
                    wp_insert_post( $args );
                } else {
                    // Update with revised analysis
                    $args['ID'] = $existing_id;
                    wp_update_post( $args );
                }
            }

            return true;
        }

        /** 
         * insert_apod
         * 
         * Processes and inserts Astronomy Picture of the Day from NASA's APOD API.
         * Stores image metadata and explanation. Does not insert duplicates.
         * Image files are downloaded separately by SGU_Space_Imagery class.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param array $data APOD object from NASA APOD API containing title, date, explanation, URLs
         * 
         * @return bool True if insert succeeded, false if data empty or insert failed
         * 
        */
        public function insert_apod( array $the_data ) : bool {
            
            // Early return if no data
            if( ! $the_data ) { 
                return false; 
            }
            // we only need the first item
            $data = $the_data[0];
            
            // if there's no data
            if(count($data) == 0) {
                return false;
            }
            $data = ( isset( $data[0] ) ) ? $data[0] : $data;

            // Extract and sanitize APOD data
            $title = sanitize_text_field( $data['title'] );
            $date = sanitize_text_field( date( 'Y-m-d H:i:s', strtotime( $data['date'] ) ) );
            $content = sanitize_text_field( $data['explanation'] );
            
            // Prefer HD URL if available, fall back to standard
            $media = sanitize_url( ( $data['hdurl'] ) ?? ( $data['url'] ) ?? '' );

            // let's see if the media file has a video extension...
            $is_actually_vid = preg_match( '/\.(mp4|avi|mov|mkv|webm|flv|wmv)$/i', $media );

            // Extract copyright or default to NASA
            $copyright = sanitize_text_field( ( $data['copyright'] ) ?? 'NASA/JPL' );
            
            // Media type: 'image' or 'video'
            $media_type = ( $is_actually_vid ) ? 'video' : sanitize_text_field( $data['media_type'] );

            // if the media type is not an image or video, skip it
            if( ! in_array( $media_type, ['video', 'image'] ) ) {
                return false;
            }

            // Check if APOD already exists by title
            $existing_id = ( SGU_Static::get_id_from_slug( sanitize_title( $title ), 'sgu_apod' ) ) ?: 0;

            // Build post data
            $args = [
                'post_status' => 'publish',
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'sgu_apod',
                'post_author' => 16,
                'post_date' => $date,
            ];

            // Only insert if new - APODs are never updated
            if( $existing_id == 0 ) {
                $existing_id = wp_insert_post( $args );
                
                // ONLY blank out local_media on NEW posts
                // This allows imagery sync to populate it later
                // Store APOD metadata in post meta
                update_post_meta( $existing_id, 'sgu_apod_local_media_type', $media_type );  // 'image' or 'video'
                update_post_meta( $existing_id, 'sgu_apod_orignal_media', $media );          // Original NASA URL
                update_post_meta( $existing_id, 'sgu_apod_local_media', '' );                // Local URL (filled by imagery sync)
                update_post_meta( $existing_id, 'sgu_apod_copyright', $copyright );          // Copyright/credit line
            }

            // Convert ID to boolean
            return filter_var( $existing_id, FILTER_VALIDATE_BOOLEAN );
        }

        /** 
         * clean_up
         * 
         * Removes duplicate posts and orphaned post meta.
         * Uses optimized bulk queries for performance on large datasets.
         * Keeps the oldest post (lowest ID) when duplicates exist.
         * 
         * Process:
         * 1. Find duplicate posts (same title + post type)
         * 2. Delete newer duplicates and their meta
         * 3. Clean up orphaned post meta (meta without posts)
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return int Number of posts removed
         * 
        */
        public function clean_up( ) : int {
            global $wpdb;

            // Find IDs of duplicate posts using subquery
            // Keeps oldest post (lowest ID) for each title/type combination
            $delete_ids = $wpdb -> get_col(
                "SELECT p1.ID 
                FROM $wpdb->posts p1
                WHERE p1.post_type IN ('sgu_cme_alerts', 'sgu_sw_alerts', 'sgu_geo_alerts', 'sgu_sf_alerts', 'sgu_neo', 'sgu_apod')
                AND EXISTS (
                    SELECT 1 
                    FROM $wpdb->posts p2 
                    WHERE p2.post_title = p1.post_title 
                    AND p2.post_type = p1.post_type
                    AND p2.ID < p1.ID
                )"
            );

            $removed_count = 0;

            // Process deletions if duplicates found
            if( ! empty( $delete_ids ) ) {
                
                // Delete in batches of 100 to avoid query length limits
                $batches = array_chunk( $delete_ids, 100 );
                
                foreach( $batches as $batch ) {
                    // Build comma-separated ID list with proper escaping
                    $ids_string = implode( ',', array_map( 'absint', $batch ) );
                    
                    // Delete post meta first (avoid foreign key issues)
                    $wpdb -> query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($ids_string)" );
                    
                    // Delete posts
                    $wpdb -> query( "DELETE FROM $wpdb->posts WHERE ID IN ($ids_string)" );
                }
                
                // Track total removed
                $removed_count = count( $delete_ids );
            }

            // Clean up orphaned post meta
            // Uses LEFT JOIN to find meta rows without corresponding posts
            $wpdb -> query(
                "DELETE pm FROM $wpdb->postmeta pm 
                LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id 
                WHERE p.ID IS NULL"
            );

            return $removed_count;
        }
    }
}
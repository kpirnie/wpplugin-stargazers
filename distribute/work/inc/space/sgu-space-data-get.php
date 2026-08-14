<?php
/** 
 * Space Data
 * 
 * This file contains the space data methods for querying
 * astronomy-related custom post types. Handles all GET operations
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
if( ! class_exists( 'SGU_Space_Data_Get' ) ) {

    /** 
     * Class SGU_Space_Data
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
    class SGU_Space_Data_Get {

        /** 
         * get_paginated_posts
         * 
         * Generic method for paginated post queries with automatic caching.
         * This consolidates repeated WP_Query patterns across all space data types,
         * reducing code duplication and ensuring consistent caching behavior.
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $post_type The custom post type to query (e.g., 'sgu_cme_alerts')
         * @param int $paged Current page number for pagination (default: 1)
         * @param int $posts_per_page Number of posts per page (default: 6)
         * @param array $filter Filter for the query (default: empty array)
         * @param string $search Search string for the query
         * 
         * @return object|bool WP_Query object containing posts, or false on failure
         * 
        */
        private function get_paginated_posts( string $post_type, int $paged = 1, int $posts_per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            
            // Build unique cache key combining group name and page number
            $cache_key = "{$post_type}_{$paged}";
            
            // Attempt to retrieve cached query results
            $cached = wp_cache_get( $cache_key, $post_type );
            
            // Return cached data if available, avoiding expensive database query
            if( $cached !== false ) {
                return $cached;
            }
            
            // Build query arguments for WP_Query
            $args = [
                'post_type' => $post_type,              // Which CPT to query
                'posts_per_page' => $posts_per_page,    // Limit results per page
                'orderby' => 'date',                     // Sort by publication date
                'order' => 'DESC',                       // Newest first
                'paged' => $paged ?: 1                   // Current page, default to 1
            ];

            // if there's a filter or search argument
            if( ( isset( $filter ) && is_array( $filter ) ) || ! empty( $search ) ) {
                $args = array_merge( $args, is_array( $filter ) ? $filter : [], ! empty( $search ) ? ['s' => $search] : [] );
            }
            
            // Execute the WordPress query
            $query = new WP_Query( $args );
            
            // Cache the query object for 24 hours to reduce database load
            wp_cache_add( $cache_key, $query, $post_type, DAY_IN_SECONDS );
            
            return $query;
        }

        /** 
         * get_latest_alerts
         * 
         * Retrieves the single most recent alert post from each alert type.
         * Used primarily for the "Latest Alerts" dashboard widget showing
         * the most current space weather conditions across all categories.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @return object|bool Object containing latest posts by type, or false if none found
         * 
        */
        public function get_latest_alerts( ) : object|bool {
            
            // Check cache first to avoid repeated queries
            $cached = wp_cache_get( 'ussg_latest_alerts', 'ussg_latest_alerts' );
            
            if( $cached !== false ) {
                return $cached;
            }
            
            // Define all alert custom post types
            $cpts = ['sgu_cme_alerts', 'sgu_sw_alerts', 'sgu_geo_alerts', 'sgu_sf_alerts'];
            
            // Array to store latest post from each type
            $alerts = [];
            
            // Loop through each alert type
            foreach( $cpts as $cpt ) {
                
                // Query for single most recent post of this type
                $args = [
                    'post_type' => $cpt,
                    'posts_per_page' => 1,      // Only need the latest
                    'orderby' => 'date',
                    'order' => 'DESC',
                ];
                
                // Execute query
                $query = new WP_Query( $args );
                
                // Store the posts array (or false if none) keyed by CPT
                $alerts[$cpt] = $query -> posts ?: false;
            }
            
            // Convert associative array to object for consistent return type
            $alerts = (object) $alerts;
            
            // Cache for 24 hours - these don't change frequently
            wp_cache_add( 'ussg_latest_alerts', $alerts, 'ussg_latest_alerts', DAY_IN_SECONDS );
            
            return $alerts;
        }

        /** 
         * get_cme_alerts
         * 
         * Retrieves paginated Coronal Mass Ejection alert posts.
         * CMEs are large expulsions of plasma from the Sun's corona.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_cme_alerts( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_cme_alerts', $paged, $per_page, $filter, $search );
        }

        /** 
         * get_solar_flare_alerts
         * 
         * Retrieves paginated solar flare alert posts.
         * Solar flares are sudden flashes of increased brightness on the Sun.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_solar_flare_alerts( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_sf_alerts', $paged, $per_page, $filter, $search );
        }

        /** 
         * get_space_weather_alerts
         * 
         * Retrieves paginated space weather alert posts.
         * Space weather includes conditions in space that can affect Earth.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_space_weather_alerts( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_sw_alerts', $paged, $per_page, $filter, $search );
        }

        /** 
         * get_geo_magnetic_alerts
         * 
         * Retrieves paginated geomagnetic storm alert posts.
         * Geomagnetic storms are disturbances of Earth's magnetosphere.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_geo_magnetic_alerts( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_geo_alerts', $paged, $per_page, $filter, $search );
        }

        /** 
         * get_apod
         * 
         * Retrieves the current Astronomy Picture of the Day post.
         * NASA's APOD features a different astronomical image each day.
         * Typically used for homepage display of today's featured image.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @return object|bool WP_Query object with single post, or false if none found
         * 
        */
        public function get_apod( ) : object|bool {
            
            // Check cache - APOD only changes once per day
            $cached = wp_cache_get( 'ussg_todays_apod', 'ussg_todays_apod' );
            
            if( $cached !== false ) {
                return $cached;
            }
            
            // Query for single most recent APOD post
            $args = [
                'post_type' => 'sgu_apod',
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            ];
            
            $query = new WP_Query( $args );
            
            // Cache for full day since APOD updates once daily
            wp_cache_add( 'ussg_todays_apod', $query, 'ussg_todays_apod', DAY_IN_SECONDS );
            
            return $query;
        }

        /** 
         * get_neos
         * 
         * Retrieves paginated Near Earth Object posts.
         * NEOs are asteroids and comets with orbits that bring them close to Earth.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_neos( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_neo', $paged, $per_page, $filter, $search );
        }

        /** 
         * get_apods
         * 
         * Retrieves paginated archive of Astronomy Picture of the Day posts.
         * Used for browsing historical APOD entries.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @param int $paged The current page of records (default: 1)
         * @param int $per_page The number of records per page (default: 6)
         * 
         * @return object|bool WP_Query object with posts, or false if none found
         * 
        */
        public function get_apods( int $paged = 1, int $per_page = 6, array $filter = [], string $search = '' ) : object|bool {
            return $this -> get_paginated_posts( 'sgu_apod', $paged, $per_page, $filter, $search );
        }

    }

}
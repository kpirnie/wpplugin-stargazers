<?php
/** 
 * Admin Columns
 * 
 * This class will control the cpt admin columns
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

if( ! class_exists( 'SGU_CPT_Admin_Cols' ) ) {

    /** 
     * Class SGU_CPT_Admin_Cols
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
    */
    class SGU_CPT_Admin_Cols {

        /** 
         * init
         * 
         * Initilize the class
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        public function init( ): void {

            // setup all admin column configurations
            $this -> setup_admin_columns( );

            // configure CPT admin links and styling
            $this -> cpt_links( );

        }

        /** 
         * setup_admin_columns
         * 
         * Configure admin columns for all custom post types
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void This method returns nothing
         * 
        */
        private function setup_admin_columns( ) : void {

            // define CPTs that use simple column layout (checkbox, title, date)
            $simple_cols = ['sgu_cme_alerts', 'sgu_sw_alerts', 'sgu_geo_alerts', 'sgu_sf_alerts', 'sgu_neo'];
            
            // loop through simple column CPTs and add filters
            foreach ( $simple_cols as $cpt ) {

                // add filter to modify columns for this CPT
                add_filter( "manage_{$cpt}_posts_columns", function( $_cols ) {

                    // return the standard column configuration
                    return [
                        'cb' => __( 'Select All', 'sgup' ),
                        'title' => __( 'Title', 'sgup' ),
                        'date' => __( 'Date', 'sgup' ),
                    ];

                } );

            }

            // setup APOD columns with media preview
            add_filter( 'manage_sgu_apod_posts_columns', function( $_cols ) {

                // return columns including media preview
                return [
                    'cb' => __( 'Select All', 'sgup' ),
                    'media' => __( 'Media', 'sgup' ),
                    'title' => __( 'Title', 'sgup' ),
                    'date' => __( 'Date', 'sgup' ),
                ];

            } );

            // populate the custom media column for APOD
            add_action( 'manage_sgu_apod_posts_custom_column', function( $_col, $_pid ) {

                // check if we're rendering the media column
                if( 'media' === $_col ) {

                    // get the media URL from post meta
                    $_media = get_post_meta( $_pid, 'sgu_apod_local_media', true );

                    // get the media type (image or video)
                    $_media_type = get_post_meta( $_pid, 'sgu_apod_local_media_type', true );
                    
                    // check if it's an image
                    if( $_media_type == 'image' ) {

                        // output image tag
                        echo '<img src="' . esc_url( $_media ) . '" alt="' . esc_attr( get_the_title( $_pid ) ) . '" style="height:125px;" />';

                    } else {

                        // output object tag for video
                        echo '<object height="125" data="' . esc_url( $_media ) . '"></object>';

                    }

                }

            }, 10, 2);

            // clean up
            unset( $simple_cols );

        }

        /** 
         * cpt_links
         * 
         * Remove and format admin links for specified custom post types
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void This method returns nothing
         * 
        */
        private function cpt_links( ) : void {

            // define CPTs that should have restricted admin links
            $cpts = ['sgu_cme_alerts', 'sgu_sw_alerts', 'sgu_geo_alerts', 'sgu_sf_alerts', 'sgu_neo', 'sgu_apod'];

            // remove edit, view, and quick edit links from post rows
            add_filter( 'post_row_actions', function( $acts, $post ) use ( $cpts ) {

                // check if current post type matches our CPT list
                if( in_array( $post -> post_type, $cpts ) ) {

                    // remove the edit, view, and inline edit actions
                    unset( $acts['edit'], $acts['view'], $acts['inline hide-if-no-js'] );

                }

                // return the modified actions array
                return $acts;

            }, 10, 2 );

            // add CSS to disable title link pointer events
            add_action( 'admin_head', function( ) use ( $cpts ) {

                // get the current post type from global
                global $typenow;

                // check if we're on one of our CPT screens
                if( in_array( $typenow, $cpts ) ) {

                    // output CSS to disable title link interactions
                    echo '<style>.row-title{pointer-events:none;cursor:default;color:#000;}</style>';

                }

            } );

            // clean up
            unset( $cpts );

        }

    }
    
}

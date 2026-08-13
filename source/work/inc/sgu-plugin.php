<?php
/** 
 * Plugin Class
 * 
 * This is the primary plugin class file. It will be responsible for pulling together
 * the activation and deactivation of the plugin itself
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

if( ! class_exists( 'SGU_Plugin' ) ) {

    /** 
     * Class SGU_Plugin
     * 
     * The primary theme class
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
    */
    class SGU_Plugin {

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

            // Plugin De-Activation.  We don't waht to do anything here, so just return false
            register_deactivation_hook( SGUP_PATH . '/' . SGUP_FILENAME, function( ) : bool { return false; } );

            // now process the activation 
            $this -> activation( );

        }

        /** 
         * activation
         * 
         * Process the plugin activation
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function activation( ) : void {

            // Plugin Activation
            register_activation_hook( SGUP_PATH . '/' . SGUP_FILENAME, function( $_network ) : void {

                // check the PHP version, and deny if lower than 8.1
                if ( version_compare( PHP_VERSION, '8.4', '<' ) ) {

                    // it is, so throw and error message and exit
                    wp_die( __( '<h1>PHP To Low</h1><p>Due to the nature of this plugin, it cannot be run on lower versions of PHP.</p><p>Please contact your hosting provider to upgrade your site to at least version 8.4.</p>', 'sgup' ), 
                        __( 'Cannot Activate: PHP To Low', 'sgup' ),
                        array(
                            'back_link' => true,
                        ) );

                }

                // check if we tried to network activate this plugin
                if( is_multisite( ) && $_network ) {

                    // we did, so... throw an error message and exit
                    wp_die( 
                        __( '<h1>Cannot Network Activate</h1><p>Due to the nature of this plugin, it cannot be network activated.</p><p>Please go back, and activate inside your subsites.</p>', 'sgup' ), 
                        __( 'Cannot Network Activate', 'sgup' ),
                        array(
                            'back_link' => true,
                        ) 
                    );
                }

                // flush the rewrites for the CPT permalinks
                flush_rewrite_rules( );

            } );

        }

    }
}
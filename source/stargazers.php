<?php

// We don't want to allow direct access to this
defined( 'ABSPATH' ) OR die( 'No direct script access allowed' );

/*
Plugin Name: US Star Gazers
Description: generates all the functionality needed to display the weather and space info for the site
Plugin URI: https://kevinpirnie.com
Author: Kevin C. Pirnie
Author URI: https://kevinpirnie.com/
Requires at least: 6.0.9
Requires PHP: 8.1
Version: 0.0.1
Network: false
Text Domain: sgup
License: MIT
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// setup the full page to this plugin
define( 'SGUP_PATH', dirname( __FILE__ ) );

// setup the directory name
define( 'SGUP_DIRNAME', basename( dirname( __FILE__ ) ) );

// setup the primary plugin file name
define( 'SGUP_FILENAME', basename( __FILE__ ) );

// let's define our plugin URI
defined( 'SGUP_URI' ) || define( 'SGUP_URI', plugins_url( '', __FILE__ ) . '/' );

// fire us up on initialization
add_action( 'init', function( ) {

	// include our autoloader
    include_once SGUP_PATH . '/vendor/autoload.php';

    // we can fire up the rest of the theme's functionality now
    $sgu = new SGUP( );

    // initialize the theme
    $sgu -> init( );

    // clean up
    unset( $sgu );
    
}, 5 );

// hook into WP CLI initialization, if it's being utilized
if( defined( 'WP_CLI' ) ) {

    // hook into the cli initialization
    add_action( 'cli_init', function( ) : void {

        // include our autoloader
        include_once SGUP_PATH . '/vendor/autoload.php';

        // create sync commands
        WP_CLI::add_command( 'sgu sync data', ['SGU_Sync', 'sync_the_data'], 
            ['shortdesc' => 'This method syncs the remote data to WordPress.  We then utilize WP to display.'] );
        WP_CLI::add_command( 'sgu sync imagery', ['SGU_Sync', 'sync_the_imagery'], 
            ['shortdesc' => 'This method syncs the remote imagery to WordPress.  We then utilize WP to display.'] );
        WP_CLI::add_command( 'sgu sync both', ['SGU_Sync', 'sync_both'], 
            ['shortdesc' => 'This method syncs both the data and the imagery.  We then utilize WP to display.'] );
        WP_CLI::add_command( 'sgu sync cleanup', ['SGU_Sync', 'perform_cleanup'], 
            ['shortdesc' => 'This method cleans up the data.'] );

        // Historical sync commands
        WP_CLI::add_command( 'sgu historical apod', function( $args, $assoc_args ) {
                $sync = new SGU_Sync( );
                $start = isset( $assoc_args['start'] ) ? $assoc_args['start'] : '2015-01-01';
                $end = isset( $assoc_args['end'] ) ? $assoc_args['end'] : null;
                $sync -> sync_historical_apod( $start, $end );
            }, 
            ['shortdesc' => 'Sync historical APOD data from NASA (DATE: Y-m-d format (default: 2015-01-01))'] );
        WP_CLI::add_command( 'sgu historical imagery', ['SGU_Sync', 'sync_historical_imagery'], 
            ['shortdesc' => 'Sync all missing APOD imagery (one-time bulk download)'] );
        WP_CLI::add_command( 'sgu historical both', function( $args, $assoc_args ) {
                $sync = new SGU_Sync( );
                $start = isset( $assoc_args['start'] ) ? $assoc_args['start'] : '2015-01-01';
                $end = isset( $assoc_args['end'] ) ? $assoc_args['end'] : null;
                $sync -> sync_both_historical( $start, $end );
            }, 
            ['shortdesc' => 'Sync both historical APOD data and imagery (DATE: Y-m-d format (default: 2015-01-01))'] );

        // the help command
        WP_CLI::add_command( 'sgu help', function( ) {
    
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '=', 76 ) . '%n' ) );
            WP_CLI::line( WP_CLI::colorize( '%YSGU (US Star Gazers) CLI Commands%n' ) );
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '=', 76 ) . '%n' ) );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%CREGULAR SYNC COMMANDS:%n' ) );
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '-', 76 ) . '%n' ) );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu sync data%n' ) );
            WP_CLI::line( '  Syncs the remote data to WordPress (CME, Solar Flares, NEO, APOD, etc.)' );
            WP_CLI::line( '  Arguments: None' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu sync imagery%n' ) );
            WP_CLI::line( '  Syncs remote imagery to WordPress media library' );
            WP_CLI::line( '  Arguments: None' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu sync both%n' ) );
            WP_CLI::line( '  Syncs both data and imagery in one command' );
            WP_CLI::line( '  Arguments: None' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu sync cleanup%n' ) );
            WP_CLI::line( '  Cleans up duplicate posts and optimizes the database' );
            WP_CLI::line( '  Arguments: None' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%CHISTORICAL SYNC COMMANDS:%n' ) );
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '-', 76 ) . '%n' ) );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu historical apod%n' ) );
            WP_CLI::line( '  One-time bulk import of historical APOD data from NASA' );
            WP_CLI::line( '  Arguments:' );
            WP_CLI::line( '    --start=<date>  Start date in Y-m-d format (default: 2015-01-01)' );
            WP_CLI::line( '    --end=<date>    End date in Y-m-d format (default: today)' );
            WP_CLI::line( '  Example: wp sgu historical apod --start=2020-01-01 --end=2020-12-31' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu historical imagery%n' ) );
            WP_CLI::line( '  One-time bulk download of all missing APOD imagery' );
            WP_CLI::line( '  Arguments: None' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%Gwp sgu historical both%n' ) );
            WP_CLI::line( '  Syncs both historical APOD data and imagery (one-time bulk import)' );
            WP_CLI::line( '  Arguments:' );
            WP_CLI::line( '    --start=<date>  Start date in Y-m-d format (default: 2015-01-01)' );
            WP_CLI::line( '    --end=<date>    End date in Y-m-d format (default: today)' );
            WP_CLI::line( '  Example: wp sgu historical both --start=2015-01-01' );
            WP_CLI::line( '' );
            
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '=', 76 ) . '%n' ) );
            WP_CLI::line( WP_CLI::colorize( '%YNOTES:%n' ) );
            WP_CLI::line( '  - Historical sync commands are for one-time bulk imports only' );
            WP_CLI::line( '  - Regular sync commands should be used for daily updates via cron' );
            WP_CLI::line( '  - All sync operations require proper API keys configured in WordPress' );
            WP_CLI::line( WP_CLI::colorize( '%B' . str_repeat( '=', 76 ) . '%n' ) );
            
        }, 
            ['shortdesc' => 'Display help information for all SGU CLI commands'] );

    }, PHP_INT_MAX );

}

// fire this up in admin_init to inject it
add_action( 'admin_init', function( ) {

    /**
     * CMB2 Conditional Fields Handler
     */

    add_action('admin_footer', 'sgu_cmb2_conditional_fields');
    function sgu_cmb2_conditional_fields() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            function handleConditionals() {
                $('[data-conditional-id]').each(function() {
                    var $wrapper = $(this);
                    var targetId = $(this).data('conditional-id');
                    var targetValue = $(this).data('conditional-value');
                    var $target = $('#' + targetId);
                    
                    if (!$target.length) return;
                    
                    var shouldShow = false;
                    
                    if ($target.is(':checkbox')) {
                        shouldShow = (targetValue === 'on') ? $target.is(':checked') : !$target.is(':checked');
                    } else {
                        shouldShow = $target.val() == targetValue;
                    }
                    
                    shouldShow ? $wrapper.show() : $wrapper.hide();
                });
            }
            
            handleConditionals();
            $('.cmb2-wrap').on('change', 'input, select, textarea', handleConditionals);
        });
        </script>
        <?php
    }

    // remove these
    add_filter( 'register_block_type_args', function( $args, $name ) {
        if ( str_starts_with( $name, 'sgup/' ) ) {
            $args['supports']['customClassName'] = false;
            $args['supports']['className'] = false;
        }
        return $args;
    }, 10, 2 );

}, 999 );

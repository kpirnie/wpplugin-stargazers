<?php

/** 
 * Plugin Class
 * 
 * This is the primary plugin class file. It will be responsible for pulling together everything for us to use
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
 */

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

if (! class_exists('SGUP')) {

    /** 
     * Class SGUP
     * 
     * The primary theme class
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
     */
    class SGUP
    {

        /** 
         * init
         * 
         * Initilize the plugin
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         */
        public function init(): void
        {

            // hold our classes and the public method we'll be using
            $plugin_classes = array(
                ['class' => 'SGU_Plugin', 'method' => null],
                ['class' => 'SGU_CPTs', 'method' => null],
                ['class' => 'SGU_CPT_Settings', 'method' => null],
                ['class' => 'SGU_CPT_Admin_Cols', 'method' => null],
                ['class' => 'SGU_Settings', 'method' => null],
                ['class' => 'SGU_Sync', 'method' => '__init'],
                ['class' => 'SGU_Space_Blocks', 'method' => null],
                ['class' => 'SGU_Weather_Location', 'method' => null],
                ['class' => 'SGU_Weather_Blocks', 'method' => null],
                ['class' => 'SGU_Space_API', 'method' => null],
                ['class' => 'SGU_USNO_API', 'method' => null],
            );

            // loop over each item
            foreach ($plugin_classes as $item) {

                // make sure the class actually exists
                if (class_exists($item['class'])) {

                    // fire it up
                    $instance = new $item['class']();

                    // if the method is empty
                    if (empty($item['method'])) {

                        // fire up the class initializers
                        $instance->init();

                        // otherwise
                    } else {

                        // fire up the class initializers
                        $instance->{$item['method']}();
                    }

                    // now clean up the instance
                    unset($instance);
                }
            }

            // now we can clean up the class array
            unset($theme_classes);

            // Add a couple mime types to allow for uploads
            add_filter('upload_mimes', function ($mimes) {
                $mimes['gif'] = 'image/gif';
                $mimes['svg'] = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';
                return $mimes;
            });

            // register the scripts and styles
            add_action('wp_enqueue_scripts', [$this, 'register_light_pollution_assets']);

            // hopefully this helps with apod videos
            add_action('send_headers', function () {
                header_remove('X-Frame-Options');
            });
        }

        /**
         * Register light pollution map assets
         * 
         */
        function register_light_pollution_assets(): void
        {

            $plugin_url = plugins_url('/', SGUP_PATH . '/' . SGUP_FILENAME);
            $version    = defined('WP_DEBUG') && WP_DEBUG ? time() : '8.4.0';

            // Register Leaflet CSS
            wp_register_style(
                'leaflet',
                $plugin_url . 'assets/vendor/leaflet/leaflet.css',
                [],
                '1.9.4'
            );

            // Register Leaflet JS
            wp_register_script(
                'leaflet',
                $plugin_url . 'assets/vendor/leaflet/leaflet.js',
                [],
                '1.9.4',
                true
            );

            // Register light pollution map CSS
            wp_register_style(
                'sgu-light-pollution-map',
                $plugin_url . 'assets/light-pollution-map.css',
                ['leaflet'],
                $version
            );

            // Register light pollution map JS
            wp_register_script(
                'sgu-light-pollution-map',
                $plugin_url . 'assets/light-pollution-map.js',
                ['leaflet'],
                $version,
                true
            );

            // Register placeholder/loading spinner styles for AJAX weather blocks
            wp_register_style(
                'sgu-weather-blocks',
                $plugin_url . 'assets/weather-blocks.css',
                [],
                $version
            );

            // Register the sky tonight styles
            wp_register_style(
                'sgu-sky-tonight',
                $plugin_url . 'assets/sky-tonight.css',
                [],
                $version
            );

            // Enqueue placeholder styles globally since blocks can appear on any page
            wp_enqueue_style('sgu-weather-blocks');
        }
    }
}

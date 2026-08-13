<?php
/** 
 * Settings Class
 * 
 * This class controls the plugins settings
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// pull our framework
use \KP\WPFieldFramework\Loader;

// make sure the class doesnt already exist
if( ! class_exists( 'SGU_Settings' ) ) {

    /** 
     * Class SGU_Settings
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
    */
    class SGU_Settings {

        // hold the framework
        private $fw;

        // fire us up
        public function __construct( ) {
            // load up our framework
            $this -> fw = Loader::init( );
        }

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

            // add the primary settings page
            $this->add_settings( );

        }

        /** 
         * add_settings
         * 
         * Add our admin settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_settings(): void {

            $settings_id = 'sgu_api_settings';

            // create the main options page
            $this -> fw -> addOptionsPage([
                'option_key' => $settings_id,
                'page_title'  => 'US Star Gazers Options',
                'menu_title'  => 'SGU Options',
                'capability'  => 'manage_options',
                'menu_slug'   => 'sgu-options',
                'icon_url'    => 'dashicons-location-alt',
                'position'    => 2,
                'tabs'       => [
                    'apis' => $this -> api_key_fields(),
                    'endpoints' => $this -> endpoint_fields(),
                ],
            ]);

        }

        /** 
         * api_key_fields
         * 
         * Add our api key fields
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function api_key_fields(  ) : array {

            // return the field configuration array
            return [
                    'title'    => 'API Settings',
                    'sections' => [
                        'apiinfo' => [
                            'title'  => 'API Info',
                            'fields' => [
                                [
                                    'id' => 'om_info',
                                    'type' => 'html',
                                    'label' => __( 'Open-Meteo', 'sgup'),
                                    'content' => __( 'Open-Meteo provides free weather data without requiring an API key. No configuration needed for basic weather functionality.', 'sgup' ),
                                ],
                                [
                                    'id' => 'sgup_noaa_user_agent',
                                    'type' => 'text',
                                    'label' => __('NOAA User Agent (Optional)', 'sgup'),
                                    'description' => __( 'NOAA Weather API does not require keys, but you can customize the User-Agent string. See: https://www.weather.gov/documentation/services-web-api', 'sgup' ),
                                    'default' => 'US Star Gazers (iam@kevinpirnie.com)',
                                ],
                            ],
                        ],
                        'apikeys' => [
                            'title'  => 'API Keys',
                            'fields' => [
                                [
                                    'id' => 'sgu_aa_group',
                                    'type' => 'repeater',
                                    'label' => __('AstronomyAPI Keys', 'sgup'),
                                    'sublabel' => __( 'See here to obtain the keys: https://docs.astronomyapi.com/', 'sgup' ),
                                    'min_rows' => 1,
                                    'max_rows' => 25,
                                    'collapsed' => true,
                                    'sortable' => true,
                                    'row_label' => __('Key', 'sgup' ),
                                    'button_label' => __('Add a Key', 'sgup' ),
                                    'fields' => [
                                        [
                                            'id'    => 'sgup_aa_api_id',
                                            'type'  => 'text',
                                            'label' => __('App ID', 'sgup' ),
                                            'inline' => true,
                                        ],
                                        [
                                            'id'    => 'sgup_aa_api_secret',
                                            'type'  => 'text',
                                            'label' => __('App Secret', 'sgup' ),
                                            'inline' => true,
                                        ],

                                    ],
                                ],
                            ],
                        ],
                    ],
                ];        
        }

        /** 
         * endpoint_fields
         * 
         * Add our api endpoint fields
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function endpoint_fields( ) : array {

            // return the array for the fields
            return [
                    'title'    => __('API Endpoints', 'sgup'),
                    'sections' => [
                        'endpoints' => [
                            'title'  => __('Endpoints', 'sgup'),
                            'fields' => [
                                [
                                    'id'    => 'om_ep',
                                    'type'  => 'html',
                                    'label' => __( 'Open-Meteo Endpoints', 'sgup' ),
                                    'content' => __( 'Open-Meteo provides free weather data without requiring an API key. No configuration needed for basic weather functionality.', 'sgup' ),
                                ],
                                [
                                    'id'    => 'noaa_ep',
                                    'type'  => 'html',
                                    'label' => __( 'NOAA Endpoints', 'sgup' ),
                                    'content' => __( 'NOAA endpoints are pre-configured. See: https://www.weather.gov/documentation/services-web-api', 'sgup' ),
                                ],
                                [
                                    'id'    => 'sgup_aapi_endpoint',
                                    'type'  => 'text',
                                    'label' => __( 'AstronomyAPI Endpoint', 'sgup' ),
                                    'description' => __( 'Astronomy API Endpoint can be found here: https://docs.astronomyapi.com/', 'sgup' ),
                                ],
                                
                            ],
                        ],
                    ],
                ];
        }

    }
}

<?php
/** 
 * CPT Settings Class
 * 
 * This class will control the cpts settings
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

if( ! class_exists( 'SGU_CPT_Settings' ) ) {

    /** 
     * Class SGU_CPT_Settings
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
    */
    class SGU_CPT_Settings {

        // hold the framework
        private $fw;

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

            // load up our framework
            $this -> fw = Loader::init( );

            // add cme settings
            $this -> add_cme_settings( );

            // add solar flare settings
            $this -> add_flare_settings( );
            
            // add geomagnetic settings
            $this -> add_geomag_settings( );

            // add spaceweather settings
            $this -> add_sw_settings( );

            // add neo settings
            $this -> add_neo_settings( );

            // add the apod settings
            $this -> add_apod_settings( );

        }

        /** 
         * add_cme_settings
         * 
         * Add in the CME settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_cme_settings( ) : void {

            // setup the cme settings ID
            $cme_id = 'sgup_cme_settings';

            // add in the CME options
            $this -> fw -> addOptionsPage([
                'option_key' => $cme_id,
                'page_title'  => __( 'US Stargazers CME Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'cme-settings',
                'parent_slug' => 'edit.php?post_type=sgu_cme_alerts',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_cme_api_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://api.nasa.gov/. If your endpont needs a key attached to it, add it with a %s', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_cme_api_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_cme_api_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],

                                ],
                            ],

                        ],
                    ],
                ],
            ] );

        }

        /** 
         * add_flare_settings
         * 
         * Add in the Solar Flare settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_flare_settings( ) : void {

            // setup the cme settings ID
            $flare_id = 'sgup_flare_settings';

            $this -> fw -> addOptionsPage([
                'option_key' => $flare_id,
                'page_title'  => __( 'US Stargazers Solar Flare Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'flare-settings',
                'parent_slug' => 'edit.php?post_type=sgu_sf_alerts',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_flare_api_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://api.nasa.gov/. If your endpont needs a key attached to it, add it with a %s', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_flare_use_cme',
                                'type' => 'checkbox',
                                'label' => __('Use CME Keys?', 'sgup'),
                                'checkbox_label' => __('Should we use the same keys you set for the CME\'s?', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_flare_api_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_flare_api_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],
                                ],
                                'conditional' => [ 'sgup_flare_use_cme' => [ '==' => true ], ],
                                'before_field' => '<div data-conditional-id="sgup_flare_use_cme" data-conditional-value="off">',
                                'after_field' => '</div>',
                            ],

                        ],
                    ],
                ],
            ] );

        }

        /** 
         * add_geomag_settings
         * 
         * Add in the GeoMagnetic settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_geomag_settings( ) : void {

            // setup the cme settings ID
            $geomag_id = 'sgup_geomag_settings';

            $this -> fw -> addOptionsPage([
                'option_key' => $geomag_id,
                'page_title'  => __( 'US Stargazers Geo-Magnetic Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'geomag-settings',
                'parent_slug' => 'edit.php?post_type=sgu_geo_alerts',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_geomag_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://services.swpc.noaa.gov/text/', 'sgup'),
                            ],/*
                            [
                                'id' => 'sgup_flare_use_cme',
                                'type' => 'checkbox',
                                'label' => __('Use CME Keys?', 'sgup'),
                                'checkbox_label' => __('Should we use the same keys you set for the CME\'s?', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_flare_api_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_flare_api_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],
                                ],
                                'conditional' => [ 'sgup_flare_use_cme' => [ '==' => true ], ],
                                'before_field' => '<div data-conditional-id="sgup_flare_use_cme" data-conditional-value="off">',
                                'after_field' => '</div>',
                            ],*/

                        ],
                    ],
                ],
            ] );

        }

        /** 
         * add_sw_settings
         * 
         * Add in the Space Weather settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_sw_settings( ) : void {

            // setup the cme settings ID
            $sw_id = 'sgup_sw_settings';

            $this -> fw -> addOptionsPage([
                'option_key' => $sw_id,
                'page_title'  => __( 'US Stargazers Space Weather Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'sw-settings',
                'parent_slug' => 'edit.php?post_type=sgu_sw_alerts',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_sw_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://services.swpc.noaa.gov/products/', 'sgup'),
                            ],/*
                            [
                                'id' => 'sgup_flare_use_cme',
                                'type' => 'checkbox',
                                'label' => __('Use CME Keys?', 'sgup'),
                                'checkbox_label' => __('Should we use the same keys you set for the CME\'s?', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_flare_api_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_flare_api_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],
                                ],
                                'conditional' => [ 'sgup_flare_use_cme' => [ '==' => true ], ],
                                'before_field' => '<div data-conditional-id="sgup_flare_use_cme" data-conditional-value="off">',
                                'after_field' => '</div>',
                            ],*/

                        ],
                    ],
                ],
            ] );
            
        }

        /** 
         * add_neo_settings
         * 
         * Add in the Near Earth Object settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_neo_settings( ) : void {

            // setup the cme settings ID
            $cme_id = 'sgup_neo_settings';

            // add in the CME options
            $this -> fw -> addOptionsPage([
                'option_key' => $cme_id,
                'page_title'  => __( 'US Stargazers Near Earth Object Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'neo-settings',
                'parent_slug' => 'edit.php?post_type=sgu_neo',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_neo_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://api.nasa.gov/. If your endpont needs a key attached to it, add it with a %s', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_neo_use_cme',
                                'type' => 'checkbox',
                                'label' => __('Use CME Keys?', 'sgup'),
                                'checkbox_label' => __('Should we use the same keys you set for the CME\'s?', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_neo_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_neo_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],

                                ],
                            ],

                        ],
                    ],
                ],
            ] );

        }

        /** 
         * add_apod_settings
         * 
         * Add in the photo of the day settings
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
        */
        private function add_apod_settings( ) : void {

            // setup the cme settings ID
            $cme_id = 'sgup_apod_settings';

            // add in the CME options
            $this -> fw -> addOptionsPage([
                'option_key' => $cme_id,
                'page_title'  => __( 'US Stargazers Astronomy Photo of the Day Settings', 'sgup' ),
                'menu_title'  => __( 'Settings', 'sgup'),
                'capability'  => 'list_users',
                'menu_slug'   => 'apod-settings',
                'parent_slug' => 'edit.php?post_type=sgu_apod',
                'sections' => [
                    'cme_settings' => [
                        'fields' => [
                            [
                                'id' => 'sgup_apod_archive',
                                'type' => 'page_select',
                                'label' => __( 'Archive Page', 'sgup'),
                                'description' => __('Select the page to use as the archive page for the photo journal articles.', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_apod_endpoint',
                                'type' => 'text',
                                'label' => __( 'Endpoint', 'sgup'),
                                'description' => __('See here to get your endpoint: https://api.nasa.gov/. If your endpont needs a key attached to it, add it with a %s', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_apod_use_cme',
                                'type' => 'checkbox',
                                'label' => __('Use CME Keys?', 'sgup'),
                                'checkbox_label' => __('Should we use the same keys you set for the CME\'s?', 'sgup'),
                            ],
                            [
                                'id' => 'sgup_apod_keys',
                                'type' => 'repeater',
                                'label' => __('API Keys', 'sgup'),
                                'description' => __('See here to get your API keys: https://api.nasa.gov/', 'sgup'),
                                'min_rows' => 1,
                                'max_rows' => 25,
                                'collapsed' => true,
                                'sortable' => true,
                                'row_label' => __('Key', 'sgup' ),
                                'button_label' => __('Add a Key', 'sgup' ),
                                'fields' => [
                                    [
                                        'id'    => 'sgup_apod_key',
                                        'type'  => 'text',
                                        'label' => __('Key', 'sgup' ),
                                    ],

                                ],
                            ],

                        ],
                    ],
                ],
            ] );
            
        }

    }

}

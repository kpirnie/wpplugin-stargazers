<?php

/** 
 * Weather Blocks Class
 * 
 * Handles Gutenberg block registration for weather-related blocks.
 * Provides block editor integration with real-time previews.
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers Plugin
 * 
 */

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

if (! class_exists('SGU_Weather_Blocks')) {

    /** 
     * Class SGU_Weather_Blocks
     * 
     * Registers and manages Gutenberg blocks for displaying weather data.
     * 
     * Available blocks:
     * - sgup/weather-current: Current weather conditions
     * - sgup/weather-daily: Daily forecast
     * - sgup/weather-weekly: 7-day forecast
     * - sgup/weather-alerts: NOAA weather alerts
     * - sgup/weather-full: Full weather dashboard
     * - sgup/weather-location: Location picker component
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers Plugin
     * 
     */
    final class SGU_Weather_Blocks
    {

        /** @var string Block category slug */
        private const CATEGORY = 'sgup_weather';

        /** @var array Default coordinates (Hawaii) */
        private const DEFAULT_COORDS = [
            'lat' => 19.8987,
            'lon' => -155.6659,
        ];

        /** @var SGU_Weather_Data Weather data handler */
        private readonly SGU_Weather_Data $weather_data;

        /** @var object|null Cached location data */
        private $location; // cant set this

        /** @var string Resolved location name */
        private readonly string $location_name;

        /** @var float Latitude coordinate */
        private readonly float $lat;

        /** @var float Longitude coordinate */
        private readonly float $lon;

        /** @var array Block supports configuration */
        private readonly array $supports;

        /** @var array Cached weather data by type */
        private array $weather_cache = [];

        /** 
         * __construct
         * 
         * Initialize the class
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         */
        public function __construct()
        {

            // setup and cache the weather & location data for the class
            $this->weather_data = new SGU_Weather_Data();
            $location_handler = new SGU_Weather_Location();

            // hold the supports array
            $this->supports = [
                'align' => true,
                'className' => true,
                'customClassName' => true,
                'spacing' => [
                    'margin' => true,
                    'padding' => true,
                    'blockGap' => true,
                ],
            ];

            // get & setup class common data
            $this->location = $location_handler->get_stored_location();
            $this->location_name = $this->location?->name ?? 'Not Found';
            $this->lat = $this->location?->lat ?? self::DEFAULT_COORDS['lat'];
            $this->lon = $this->location?->lon ?? self::DEFAULT_COORDS['lon'];
        }

        /** 
         * init
         * 
         * Initialize the blocks system
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @return void
         * 
         */
        public function init(): void
        {

            // Register all weather blocks during WordPress initialization
            add_action('init', $this->register_blocks(...));
        }

        /** 
         * get_weather_data
         * 
         * Retrieve weather data by type with caching
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @param string $type The type of weather data to retrieve
         * 
         * @return mixed Weather data or false if type not found
         * 
         */
        private function get_weather_data(string $type): mixed
        {

            // return cached data if available
            if (isset($this->weather_cache[$type])) {
                return $this->weather_cache[$type];
            }

            // match the type and get the actual weather data needed for it
            $data = match ($type) {
                'current' => $this->weather_data->get_current_weather($this->lat, $this->lon),
                'hourly' => $this->weather_data->get_hourly_forecast($this->lat, $this->lon),
                'daily' => $this->weather_data->get_daily_forecast($this->lat, $this->lon),
                'noaa' => $this->weather_data->get_noaa_forecast($this->lat, $this->lon),
                'alerts' => $this->weather_data->get_noaa_alerts($this->lat, $this->lon),
                default => false,
            };

            // cache and return the data
            $this->weather_cache[$type] = $data;

            // return the data
            return $data;
        }

        /** 
         * base_attributes
         * 
         * Get base attributes common to most weather blocks
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @param string $default_title Default title for the block
         * @param bool $show_location_picker Whether to show location picker by default
         * 
         * @return array Base attributes array
         * 
         */
        private function base_attributes(string $default_title, bool $show_location_picker = true): array
        {

            return [
                'title' => ['type' => 'string', 'default' => $default_title],
                'showTitle' => ['type' => 'boolean', 'default' => true],
                'showLocationPicker' => ['type' => 'boolean', 'default' => $show_location_picker],
            ];
        }

        /** 
         * base_data
         * 
         * Build base data array common to all weather block templates
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @param array $attributes Block attributes
         * @param string $default_title Default title fallback
         * 
         * @return array Base data array for template
         * 
         */
        private function base_data(array $attributes, string $default_title): array
        {

            return [
                'title' => $attributes['title'] ?? $default_title,
                'show_title' => $attributes['showTitle'] ?? true,
                'show_location_picker' => $attributes['showLocationPicker'] ?? true,
                'has_location' => (bool) $this->location,
                'location' => $this->location,
                'location_name' => $this->location_name,
                'wrapper_attr' => get_block_wrapper_attributes(),
            ];
        }

        /** 
         * register_block
         * 
         * Register a single Gutenberg block with common configuration
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @param string $name Block name (e.g., 'sgup/weather-current')
         * @param array $attributes Block attributes definition
         * @param callable $render_callback Render callback function
         * @param int $api_version Block API version (default: 2)
         * 
         * @return void
         * 
         */
        private function register_block(string $name, array $attributes, callable $render_callback): void
        {

            register_block_type($name, [
                'api_version' => 3,
                'category' => self::CATEGORY,
                'render_callback' => $render_callback,
                'attributes' => $attributes,
                'supports' => $this->supports,
            ]);
        }

        /** 
         * render_placeholder
         * 
         * Renders a placeholder container for front-end AJAX loading.
         * In the block editor context, falls through to the provided
         * callback for server-side preview rendering instead.
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @param string   $type       Block type slug (e.g., 'weather-current')
         * @param array    $attributes Block attributes from the editor
         * @param callable $preview_cb Callback returning HTML for editor preview
         * 
         * @return string Placeholder HTML for front-end or preview HTML for editor
         * 
         */
        private function render_placeholder(string $type, array $attributes, callable $preview_cb): string
        {

            // No location stored — render the location form directly so the user
            if (! $this->location) {
                return SGU_Static::render_template('weather/location-picker', [
                    'title'        => __('Set Your Location', 'sgup'),
                    'show_title'   => true,
                    'compact'      => false,
                    'has_location' => false,
                    'location'     => null,
                    'wrapper_attr' => '',
                ]);
            }

            // Render the location picker once as a hidden template for JS error states to clone
            static $location_bar_printed = false;
            if (! $location_bar_printed) {
                $location_bar_printed = true;
                echo '<div id="sgu-location-bar-template" style="display:none;">'
                    . SGU_Static::render_template('weather/location-picker', [
                        'title'        => __('Set Your Location', 'sgup'),
                        'show_title'   => false,
                        'compact'      => false,
                        'has_location' => (bool) $this->location,
                        'location'     => $this->location,
                        'wrapper_attr' => '',
                    ])
                    . '</div>';
            }

            // In the block editor, render the actual preview via the provided callback
            if (defined('REST_REQUEST') && REST_REQUEST) {
                return $preview_cb();
            }

            // Generate a unique ID for this block instance so the JS
            // can match returned HTML fragments back to their containers
            $uid = $type . '_' . wp_unique_id();

            // Encode block attributes for the JS to send back with the request
            $encoded_attrs = esc_attr(wp_json_encode($attributes));

            // Output a placeholder container — JS will discover these,
            // batch-request all of them, and swap in the rendered HTML
            return sprintf(
                '<div class="sgu-weather-placeholder" data-sgu-weather-block="%s" data-sgu-uid="%s" data-sgu-attrs="%s" aria-busy="true">
                    <div class="sgu-weather-loading">
                        <span class="sgu-loading-spinner" aria-hidden="true"></span>
                    </div>
                </div>',
                esc_attr($type),
                esc_attr($uid),
                $encoded_attrs
            );
        }

        /** 
         * register_blocks
         * 
         * Register all weather Gutenberg blocks
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers Plugin
         * 
         * @return void
         * 
         */
        public function register_blocks(): void
        {

            // Register Current Weather block
            $this->register_block(
                'sgup/weather-current',
                $this->base_attributes('Current Weather') + [
                    'showDetails' => ['type' => 'boolean', 'default' => true],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-current', $attributes, function () use ($attributes): string {
                        $data = $this->base_data($attributes, 'Current Weather') + [
                            'show_details' => $attributes['showDetails'] ?? true,
                            'weather'      => $this->get_weather_data('current'),
                        ];
                        return SGU_Static::render_template('weather/current', $data);
                    });
                },
            );

            // Register Daily Forecast block
            $this->register_block(
                'sgup/weather-daily',
                $this->base_attributes("Today's Forecast") + [
                    'showHourly' => ['type' => 'boolean', 'default' => true],
                    'hoursToShow' => ['type' => 'number', 'default' => 24],
                    'useNoaa' => ['type' => 'boolean', 'default' => true],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-daily', $attributes, function () use ($attributes): string {
                        $data = $this->base_data($attributes, "Today's Forecast") + [
                            'show_hourly'   => $attributes['showHourly'] ?? true,
                            'hours_to_show' => $attributes['hoursToShow'] ?? 24,
                            'use_noaa'      => $attributes['useNoaa'] ?? true,
                            'forecast'      => $this->get_weather_data('hourly'),
                            'noaa_forecast' => $this->get_weather_data('noaa'),
                        ];
                        return SGU_Static::render_template('weather/day', $data);
                    });
                },
            );

            // Register the Hourly Breakdown
            $this->register_block(
                'sgup/weather-daily-hourly',
                $this->base_attributes("Today's Hourly Forecast") + [
                    'hoursToShow' => ['type' => 'number', 'default' => 24],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-daily-hourly', $attributes, function () use ($attributes): string {
                        $data = $this->base_data($attributes, "Today's Hourly Forecast") + [
                            'hours_to_show' => $attributes['hoursToShow'] ?? 24,
                            'forecast'      => $this->get_weather_data('hourly'),
                        ];
                        return SGU_Static::render_template('weather/hourly', $data);
                    });
                },
            );

            // Register Weekly Forecast block
            $this->register_block(
                'sgup/weather-weekly',
                $this->base_attributes('7-Day Forecast') + [
                    'daysToShow' => ['type' => 'number', 'default' => 7],
                    'useNoaa' => ['type' => 'boolean', 'default' => true],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-weekly', $attributes, function () use ($attributes): string {
                        $data = $this->base_data($attributes, '7-Day Forecast') + [
                            'days_to_show'  => $attributes['daysToShow'] ?? 7,
                            'use_noaa'      => $attributes['useNoaa'] ?? true,
                            'forecast'      => $this->get_weather_data('daily'),
                            'noaa_forecast' => $this->get_weather_data('noaa'),
                        ];
                        return SGU_Static::render_template('weather/week', $data);
                    });
                },
            );

            // Register the Extended Weekly Forecast
            $this->register_block(
                'sgup/weather-weekly-extended',
                $this->base_attributes('7-Day Extended') + [
                    'daysToShow' => ['type' => 'number', 'default' => 7],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-weekly-extended', $attributes, function () use ($attributes): string {
                        $data = $this->base_data($attributes, '7-Day Extended') + [
                            'days_to_show' => $attributes['daysToShow'] ?? 7,
                            'weather'      => $this->get_weather_data('noaa'),
                        ];
                        return SGU_Static::render_template('weather/week-extended', $data);
                    });
                },
            );

            // Register Weather Alerts block
            $this->register_block(
                'sgup/weather-alerts',
                $this->base_attributes('Weather Alerts', false) + [
                    'maxAlerts' => ['type' => 'number', 'default' => 5],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-alerts', $attributes, function () use ($attributes): string {
                        $alerts = (array) $this->get_weather_data('alerts');
                        $max    = $attributes['maxAlerts'] ?? 5;
                        $data   = $this->base_data($attributes, 'Weather Alerts') + [
                            'max_alerts' => $max,
                            'alerts'     => array_slice($alerts, 0, $max),
                        ];
                        return SGU_Static::render_template('weather/alerts', $data);
                    });
                },
            );

            // Register Full Weather Dashboard block
            $this->register_block(
                'sgup/weather-full',
                $this->base_attributes('Weather Dashboard') + [
                    'showCurrent' => ['type' => 'boolean', 'default' => true],
                    'showHourly' => ['type' => 'boolean', 'default' => true],
                    'showDaily' => ['type' => 'boolean', 'default' => true],
                    'showDailyExtended' => ['type' => 'boolean', 'default' => true],
                    'showAlerts' => ['type' => 'boolean', 'default' => true],
                    'showNoaa' => ['type' => 'boolean', 'default' => true],
                ],
                function (array $attributes): string {
                    return $this->render_placeholder('weather-full', $attributes, function () use ($attributes): string {
                        $show = [
                            'current'          => $attributes['showCurrent'] ?? true,
                            'hourly'           => $attributes['showHourly'] ?? true,
                            'daily'            => $attributes['showDaily'] ?? true,
                            'daily_extended'   => $attributes['showDailyExtended'] ?? true,
                            'alerts'           => $attributes['showAlerts'] ?? true,
                            'noaa'             => $attributes['showNoaa'] ?? true,
                        ];
                        $data = $this->base_data($attributes, 'Weather Dashboard') + [
                            'show_current'        => $show['current'],
                            'show_hourly'         => $show['hourly'],
                            'show_daily'          => $show['daily'],
                            'show_daily_extended' => $show['daily_extended'],
                            'show_alerts'         => $show['alerts'],
                            'show_noaa'           => $show['noaa'],
                            'current_weather'     => $show['current'] ? $this->get_weather_data('current') : null,
                            'hourly_forecast'     => $show['hourly'] ? $this->get_weather_data('hourly') : null,
                            'daily_forecast'      => $show['daily'] ? $this->get_weather_data('daily') : null,
                            'noaa_forecast'       => $show['noaa'] ? $this->get_weather_data('noaa') : null,
                            'alerts'              => $show['alerts'] ? $this->get_weather_data('alerts') : [],
                        ];
                        return SGU_Static::render_template('weather/dashboard', $data);
                    });
                },
            );

            // Register Location Picker block
            $this->register_block(
                'sgup/weather-location',
                [
                    'title' => ['type' => 'string', 'default' => 'Set Your Location'],
                    'showTitle' => ['type' => 'boolean', 'default' => true],
                    'compact' => ['type' => 'boolean', 'default' => false],
                ],
                function (array $attributes): string {

                    // hold the data we're going to pass to the template
                    $data = [
                        'title' => $attributes['title'] ?? 'Set Your Location',
                        'show_title' => $attributes['showTitle'] ?? true,
                        'compact' => $attributes['compact'] ?? false,
                        'has_location' => (bool) $this->location,
                        'location' => $this->location,
                        'wrapper_attr' => get_block_wrapper_attributes(),
                    ];

                    // render the template
                    return SGU_Static::render_template('weather/location-picker', $data);
                }
            );

            // Register Weather Map block
            $this->register_block(
                'sgup/weather-map',
                $this->base_attributes('Weather Map') + [
                    'mapLayer' => [
                        'type' => 'string',
                        'default' => 'clouds',
                        'enum' => ['clouds', 'wind', 'rain', 'radar', 'temp', 'rh', 'satelite', 'visibility'],
                    ],
                    'maxHeight' => ['type' => 'number', 'default' => 450],
                ],
                function (array $attributes): string {

                    // hold the data we're going to pass to the template
                    $data = $this->base_data($attributes, 'Weather Map') + [
                        'map_layer' => $attributes['mapLayer'] ?? 'clouds',
                        'max_height' => $attributes['maxHeight'] ?? 450,
                        'latitude' => $this->lat,
                        'longitude' => $this->lon,
                    ];

                    // render the template
                    return SGU_Static::render_template('weather/map', $data);
                }
            );
        }
    }
}

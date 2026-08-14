<?php

/** 
 * Static Class
 * 
 * This class will contain the static methods
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
 */

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

if (! class_exists('SGU_Static')) {

    /** 
     * Class SGU_Static
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
     */
    class SGU_Static
    {

        /** 
         * custom_youtube_embed
         * 
         * This method is utilized to properly format a video URL
         * for properly embedding videos from youtube
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $embed_url The embed video URL
         * 
         * @return string Returns corrected url to utilize with WP OEmbed
         * 
         */
        public static function custom_youtube_embed(string $embed_url): string
        {

            // Extract video ID from embed URL
            preg_match('/embed\/([^?]+)/', $embed_url, $matches);

            if (isset($matches[1])) {
                $video_id = $matches[1];
                $watch_url = 'https://www.youtube.com/watch?v=' . $video_id;

                // Get parameters from embed URL
                $query_string = parse_url($embed_url, PHP_URL_QUERY);
                parse_str($query_string, $params);

                // Add parameters to watch URL
                if (!empty($params)) {
                    $watch_url = add_query_arg($params, $watch_url);
                }

                return $watch_url;
            }

            return '';
        }

        /**
         * Render video media from various sources
         * Handles YouTube, Vimeo, direct video files, and fallback for blocked iframes
         *
         * @param string $url The video URL
         * @param string $title Title for fallback display
         * @param string $class CSS classes for video/oembed/fallback elements
         * @return string HTML output
         */
        public static function render_video_media(string $url, string $title = '', string $class = '', bool $show_controls = true): string
        {

            $class_attr = $class ? ' class="' . esc_attr($class) . '"' : '';

            // Direct video files - use HTML5 video player
            $video_extensions = ['mp4', 'webm', 'ogg', 'mov', 'm4v'];
            $parsed_path = parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($parsed_path, PATHINFO_EXTENSION));

            if (in_array($extension, $video_extensions, true)) {
                $mime_map = [
                    'mp4'  => 'video/mp4',
                    'webm' => 'video/webm',
                    'ogg'  => 'video/ogg',
                    'mov'  => 'video/mp4',
                    'm4v'  => 'video/mp4',
                ];
                $controls = ($show_controls) ? ' controls playsinline' : '';
                return '<video' . $class_attr . '' . $controls . '>
                    <source src="' . esc_url($url) . '" type="' . esc_attr($mime_map[$extension] ?? 'video/mp4') . '">
                </video>';
            }

            // YouTube
            if (preg_match('/(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
                $oembed = wp_oembed_get('https://www.youtube.com/watch?v=' . $matches[1]);
                if ($oembed) {
                    return $class ? '<div' . $class_attr . '>' . $oembed . '</div>' : $oembed;
                }
            }

            // Vimeo
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
                $oembed = wp_oembed_get('https://vimeo.com/' . $matches[1]);
                if ($oembed) {
                    return $class ? '<div' . $class_attr . '>' . $oembed . '</div>' : $oembed;
                }
            }

            // Generic oEmbed
            $oembed = wp_oembed_get($url);
            if ($oembed) {
                return $class ? '<div' . $class_attr . '>' . $oembed . '</div>' : $oembed;
            }

            // Fallback
            return '<div' . $class_attr . '>
                <a href="' . esc_url($url) . '" target="_blank" rel="noopener">' .
                esc_html($title ?: __('Watch Video', 'sgup')) . ' &#8599;' .
                '</a>
            </div>';
        }

        /** 
         * get_remote_data
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return array Returns an array of data retrieved
         * 
         */
        public static function get_remote_data(string $endpoint, array $headers = [], $should_cache = false, int $cache_length = 0, string $method = 'GET', mixed $body = null): array
        {

            // Validate HTTP method
            $method = strtoupper($method);
            if (!in_array($method, ['GET', 'POST'], true)) {
                return [
                    'success' => false,
                    'error' => 'Invalid HTTP method. Only GET and POST are supported.',
                    'data' => null
                ];
            }

            // Generate cache key based on request parameters
            $cache_key = 'wp_remote_cache_' . md5(serialize([
                'endpoint' => $endpoint,
                'headers' => $headers,
                'method' => $method,
                'body' => $body
            ]));

            // Try to get cached data if caching is enabled
            if ($should_cache) {
                $cached_response = wp_cache_get($cache_key);

                if ($cached_response !== false) {
                    return [
                        'success' => true,
                        'cached' => true,
                        'data' => $cached_response,
                        'cache_key' => $cache_key
                    ];
                }
            }

            // Prepare request arguments
            $args = [
                'method' => $method,
                'headers' => $headers,
                'timeout' => 30,
                'sslverify' => apply_filters('https_ssl_verify', true),
            ];

            // Add body for POST requests
            if ($method === 'POST' && $body !== null) {
                if (is_array($body)) {
                    $args['body'] = $body;
                } else {
                    $args['body'] = (string)$body;
                }
            }

            // Make the request
            $response = wp_remote_request($endpoint, $args);

            // Check for WP_Error
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'error' => $response->get_error_message(),
                    'error_code' => $response->get_error_code(),
                    'data' => null
                ];
            }

            // Get response information
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            // Try to decode JSON response
            $decoded_body = json_decode($response_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded_body = $response_body;
            }

            // Prepare response array
            $response_data = [
                'success' => $response_code >= 200 && $response_code < 300,
                'status_code' => $response_code,
                'headers' => wp_remote_retrieve_headers($response)->getAll(),
                'body' => $decoded_body,
                'raw_body' => $response_body,
                'response' => $response,
                'cached' => false
            ];

            // Cache the response if enabled
            if ($should_cache && $response_data['success']) {
                $group = 'wp_remote_responses';

                if ($cache_length > 0) {
                    wp_cache_set($cache_key, $response_data, $group, $cache_length);
                } else {
                    wp_cache_set($cache_key, $response_data, $group);
                }

                $response_data['cache_key'] = $cache_key;
                $response_data['cache_length'] = $cache_length;
            }

            // return the data
            return $response_data;
        }

        /** 
         * get_api_endpoint
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $which Which endpoing do we need to pull
         * 
         */
        public static function get_api_endpoint(string $which)
        {

            return match ($which) {
                'cme' => (array) self::get_sgu_option('sgup_cme_settings')->sgup_cme_api_endpoint ?: [],
                'geo' => (array) self::get_sgu_option('sgup_geomag_settings')->sgup_geomag_endpoint ?: [],
                'neo' => (array) self::get_sgu_option('sgup_neo_settings')->sgup_neo_endpoint ?: [],
                'sf' => (array) self::get_sgu_option('sgup_flare_settings')->sgup_flare_api_endpoint ?: [],
                'sw' => (array) self::get_sgu_option('sgup_sw_settings')->sgup_sw_endpoint ?: [],
                'apod' => (array) self::get_sgu_option('sgup_apod_settings')->sgup_apod_endpoint ?: [],

                default => [],  // Unknown type - return empty array
            };
        }

        /** 
         * get_api_key
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $which Which endpoing do we need to pull
         * 
         */
        public static function get_api_key(string $which): array
        {

            // get the CME keys by default
            $cme_keys = self::get_sgu_option('sgup_cme_settings')->sgup_cme_api_keys ?: [];

            // setup the return matching
            $ret = match ($which) {
                // NOAA endpoints don't require API keys
                'geo', 'sw' => [],

                // Solar Flare keys - check if user wants to share CME keys
                'sf' => (function () use ($cme_keys) {
                    // Get the "use CME keys" checkbox value
                    $use_cme = filter_var(
                        self::get_sgu_option('sgup_flare_settings')->sgup_flare_use_cme ?: false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    // Return CME keys if sharing, otherwise get dedicated flare keys
                    return $use_cme
                        ? $cme_keys
                        : (self::get_sgu_option('sgup_flare_settings')->sgup_flare_api_keys ?: []);
                })(),

                // NEO keys - check if user wants to share CME keys
                'neo' => (function () use ($cme_keys) {
                    // Get the "use CME keys" checkbox value
                    $use_cme = filter_var(
                        self::get_sgu_option('sgup_neo_settings')->sgup_neo_use_cme ?: false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    // Return CME keys if sharing, otherwise get dedicated NEO keys
                    return $use_cme
                        ? $cme_keys
                        : (self::get_sgu_option('sgup_neo_settings')->sgup_neo_keys ?: []);
                })(),

                // APOD keys - check if user wants to share CME keys
                'apod' => (function () use ($cme_keys) {
                    // Get the "use CME keys" checkbox value
                    $use_cme = filter_var(
                        self::get_sgu_option('sgup_apod_settings')->sgup_apod_use_cme ?: false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    // Return CME keys if sharing, otherwise get dedicated APOD keys
                    return $use_cme
                        ? $cme_keys
                        : (self::get_sgu_option('sgup_apod_settings')->sgup_apod_keys ?: []);
                })(),

                // AstronomyAPI keys
                'aapi' => (function () use ($cme_keys) {

                    // get the dedicated keys, and set the return
                    $aa_group = self::get_sgu_option('sgu_api_settings')->sgu_aa_group;

                    $ret = [];

                    // loop the returned setting, so we can combine the app id and secret into one string we can use later on
                    foreach ($aa_group as $item) {
                        $str = $item['sgup_aa_api_id'] . '|' . $item['sgup_aa_api_secret'];
                        $ret[] = $str;
                    }

                    // return the dedicated keys
                    return $ret ?: [];
                })(),

                // CME and unknown types use CME keys by default
                default => $cme_keys,
            };

            // return the mapped array
            return array_map(
                fn($item) => is_array($item)
                    ? ['key' => current($item)]
                    : ['key' => $item],
                $ret
            );
        }

        /** 
         * get_id_from_slug
         * 
         * This method is utilized for returning the post id from the slug
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $path Final path slug to the template to be rendered
         * @param mixed $data The data being passed to the template
         * 
         * @return string Returns the rendered template
         * 
         */
        public static function render_template(string $path, mixed $data): string
        {

            // properly format the file path
            $file_path = trim(sprintf("%s.php", $path));

            // Check theme for override first
            $theme_template = locate_template([
                "templates/$file_path",
                "sgu/$file_path",
                "stargazers/$file_path",
            ]);

            // Use theme template if found, otherwise plugin template
            $template = $theme_template ?: SGUP_PATH . "/templates/$file_path";

            // Check if template exists
            if (! file_exists($template)) {
                return sprintf('<!-- SGU Weather: Template not found: %s -->', esc_html($file_path));
            }

            // Use a closure to isolate scope
            $render = static function (string $__template, array $__data): string {
                ob_start();
                extract($__data, EXTR_SKIP); // EXTR_SKIP won't overwrite existing vars
                include $__template;
                return ob_get_clean();
            };
            return $render($template, $data);
        }

        /** 
         * get_id_from_slug
         * 
         * This method is utilized for returning the post id from the slug
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $_the_slug The posts slug
         * @param string $_post_type The post type
         * @param array $_qry_args An array of extra query arguments
         * 
         * @return int Returns the ID
         * 
         */
        public static function get_id_from_slug(string $_the_slug, string $_post_type = 'page', array $_qry_args = array()): int
        {

            // our query arguments
            $_args = array(
                'name' => $_the_slug,
                'post_type' => $_post_type,
                'post_status' => 'any',
                'posts_per_page' => 1,
            );

            // if the extra query args are present
            if (! empty($_qry_args)) {

                // append it to the existing array
                $_args = array_merge($_args, $_qry_args);
            }

            // query for our neo items
            $_qry = new WP_Query($_args);
            $_rs = $_qry->get_posts();

            // make sure we have something here
            if ($_rs) {

                // return the post id
                return $_rs[0]->ID;

                // we don't
            } else {

                // return 0 = nothing found
                return 0;
            }
        }

        /**
         * get_sgu_option
         * 
         * get an option saved from any of our settings
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $key Options array key
         * @param mixed $default Optional default value
         * 
         * @return object Option object
         */
        public static function get_sgu_option(string $key, mixed $default = false): object
        {

            // setup the return
            $ret = get_option($key, $default);

            // see if it's an array
            if (is_array($ret)) {
                return (object) $ret;
            }

            // convert the non-array return to an object for return
            return (object) array($key => $ret);
        }

        /** 
         * get_pages_array
         * 
         * get an array of all public published pages
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         */
        public static function get_pages_array(): array
        {

            // get all pages in the site
            $pages = get_pages();

            // setup the return
            $ret = array();

            // loop the pages and store the id and title in the return array
            foreach ($pages as $page) {
                $ret[$page->ID] = $page->post_title;
            }

            // return the results
            return $ret;
        }

        /** 
         * get_cpt_display_name
         * 
         * get our cpt's display name
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         */
        public static function get_cpt_display_name(string $_cpt): string
        {

            // just hold the CPT and display name in an array
            $_cpts = array(
                'page' => 'Pages',
                'post' => 'Articles',
                'sgu_cme_alerts' => 'Coronal Mass Ejection',
                'sgu_sw_alerts' => 'Space Weather',
                'sgu_geo_alerts' => 'Geo Magnetic Storm',
                'sgu_sf_alerts' => 'Solar Flare',
                'sgu_neo' => 'Near Earth Objects',
                'sgu_apod' => 'Astronomy Photos of the Day',
            );

            // return the display name
            return $_cpts[$_cpt] ?: 'Invalid Post Type';
        }

        /**
         * Safely retrieves the current page number from various WordPress pagination sources.
         *
         * Checks multiple pagination sources in order of reliability: query vars (paged, page),
         * global $wp_query, $_GET parameter, and finally the REQUEST_URI. All values are
         * sanitized using absint() to ensure positive integers only.
         *
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @global WP_Query $wp_query WordPress query object.
         *
         * @return int The current page number. Returns 1 if no valid page number is found.
         */
        public static function safe_get_paged_var(): int
        {
            global $wp_query;

            // Check multiple possible sources
            $sources = [
                get_query_var('paged'),
                get_query_var('page'),
                isset($wp_query->query_vars['paged']) ? absint($wp_query->query_vars['paged']) : 0,
                isset($_GET['paged']) ? absint($_GET['paged']) : 0
            ];

            // loop the sources
            foreach ($sources as $source) {
                // ensure it's an integer, and return it if it's greater than 0
                $source = absint($source);
                if ($source > 0) {
                    return $source;
                }
            }

            // Parse from request URI as last resort
            if (isset($_SERVER['REQUEST_URI'])) {

                // make sure to escape the url before parsing it and returning the page
                $request_uri = esc_url_raw($_SERVER['REQUEST_URI']);
                if (preg_match('/\/page\/(\d+)/', $request_uri, $matches)) {
                    return absint($matches[1]);
                }
            }

            // default return
            return 1;
        }

        /** 
         * cpt_pagination
         * 
         * Render pagination links with first and last page buttons
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param int $max_pages Maximum number of pages
         * @return string The rendered pagination HTML
         * 
         */
        public static function cpt_pagination(int $max_pages = 1, int $_paged = 1): string
        {

            // hold the output
            $out = [];

            // get current page
            $current_page = max(1, $_paged);

            // build our pagination links
            $page_links = paginate_links([
                'prev_text'          => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>',
                'next_text'          => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
                'screen_reader_text' => ' ',
                'current'            => $current_page,
                'total'              => $max_pages,
                'type'               => 'array',
                'mid_size'           => 2,
                'base'               => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format'             => '?paged=%#%',
            ]);

            // return empty string if no links
            if (! $page_links) {
                return '';
            }

            // open the pagination container
            $out[] = '<nav class="flex justify-center items-center gap-2 my-8" role="navigation" aria-label="Pagination">';

            // add first page link if we're not on page 1
            if ($current_page > 1) {
                $first_url = esc_url(get_pagenum_link(1));
                $out[] = '<a href="' . $first_url . '" class="inline-flex items-center justify-center w-10 h-10 bg-slate-800 text-slate-200 rounded hover:bg-cyan-600 hover:text-white transition-colors border border-slate-700" aria-label="First page">';
                $out[] = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>';
                $out[] = '</a>';
            }

            // add each page link
            foreach ($page_links as $link) {
                // replace WordPress classes with Tailwind classes
                $link = str_replace('page-numbers', 'inline-flex items-center justify-center min-w-10 h-10 px-3 bg-slate-800 text-slate-200 rounded hover:bg-cyan-600 hover:text-white transition-colors border border-slate-700', $link);
                $link = str_replace(
                    'class="page-numbers current"',
                    'class="font-bold inline-flex items-center justify-center min-w-10 h-10 px-3 bg-cyan-600 text-white rounded-lg border border-cyan-500"',
                    $link
                );
                $link = str_replace('dots', 'inline-flex items-center justify-center w-10 h-10 text-slate-400', $link);
                $out[] = $link;
            }

            // add last page link if we're not on the last page
            if ($current_page < $max_pages) {
                $last_url = esc_url(get_pagenum_link($max_pages));
                $out[] = '<a href="' . $last_url . '" class="inline-flex items-center justify-center w-10 h-10 bg-slate-800 text-slate-200 rounded hover:bg-cyan-600 hover:text-white transition-colors border border-slate-700" aria-label="Last page">';
                $out[] = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>';
                $out[] = '</a>';
            }

            // close the pagination container
            $out[] = '</nav>';

            // return the complete pagination HTML
            return implode('', $out);
        }

        /** 
         * parse_alert_date
         * 
         * This method is utilized to parse the date returned from some of the space API's
         * 
         * @since 7.3
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @return string The string representing the parsed date
         * 
         */
        public static function parse_alert_date(string $_str_to_parse): string
        {

            // get the year
            $_year = substr($_str_to_parse, 0, 4);

            // get the month
            $_month = substr($_str_to_parse, 5, 3);

            // get the day
            $_day = substr($_str_to_parse, 9, 2);

            // concatenate them to a date string
            $_date = $_year . '-' . $_month . '-' . $_day . ' 00:00 UTC';

            // return the date 
            return date('Y-m-d H:i:s', strtotime($_date));
        }

        /** 
         * y_or_n
         * 
         * This method is utilized to convert a boolean value to Yes or No
         * 
         * @since 7.3
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param bool $should_cache The boolean value to convert
         * 
         * @return string The Yes or No string
         * 
         */
        public static function y_or_n(bool $_val): string
        {

            // return yes or no
            return $_val == true ? 'Yes' : 'No';
        }

        /** 
         * get_attachment_id
         * 
         * This method is utilized for getting the posts attachment ID
         * by it's full URL
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $_url The url of the media to look up
         * 
         * @return void This method returns nothing
         * 
         */
        public static function get_attachment_id(string $_url): int
        {

            // Return 0 if empty URL
            if (empty($_url)) {
                return 0;
            }

            // Get upload directory info
            $upload_dir = wp_upload_dir();
            $upload_url = $upload_dir['baseurl'];

            // Remove protocol and host from URL for comparison
            $_url = preg_replace('/^https?:\/\//', '', $_url);
            $upload_url = preg_replace('/^https?:\/\//', '', $upload_url);

            // Check if URL is actually from uploads directory
            if (strpos($_url, $upload_url) === false) {
                return 0;
            }

            // Extract just the file path relative to uploads
            $file = str_replace($upload_url, '', $_url);
            $file = ltrim($file, '/');

            global $wpdb;

            // Try direct GUID match first (most reliable)
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE guid LIKE %s AND post_type = 'attachment' LIMIT 1",
                    '%' . $wpdb->esc_like($file) . '%'
                )
            );

            if ($attachment_id) {
                return (int) $attachment_id;
            }

            // Try meta_value search as fallback
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
                    $file
                )
            );

            if ($attachment_id) {
                return (int) $attachment_id;
            }

            // Last resort: search by filename only
            $filename = basename($file);
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
                    '%' . $wpdb->esc_like($filename)
                )
            );

            return $attachment_id ? (int) $attachment_id : 0;
        }

        /** 
         * attachment_url_to_path
         * 
         * This method is utilized for converting a URI to a local path
         * 
         * @since 8.0
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Star Gazers
         * 
         * @param string $_uri The uri of the file to check
         * 
         * @return bool|string Returns either false or the path of the file
         * 
         */
        public static function attachment_url_to_path(string $_uri)
        {

            // parse the uri
            $_parsed = parse_url($_uri);

            // check if it's empty
            if (empty($_parsed['path'])) return false;

            // grab the full local file path
            $_file = ABSPATH . ltrim($_parsed['path'], '/');

            // if it does exist, return the file path
            if (file_exists($_file)) return $_file;

            // default return false
            return false;
        }

        /** 
         * get_archive_url
         * 
         * Get the URL for the photo journal archive page
         * Searches for a page containing the sgup_photo_journals shortcode
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return string The URL to the archive page or '/' if not found
         * 
         */
        public static function get_archive_url(string $shortcode): string
        {

            // seutp the cache key
            $cache_key = sprintf("sgu_%s_archive_url", $shortcode);

            // Check cache first
            $cached_url = wp_cache_get($cache_key, 'sgu_urls');
            if ($cached_url !== false) {
                return $cached_url;
            }

            // Search for page with the shortcode
            $args = [
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                's' => sprintf("[%s", $shortcode),
            ];

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $url = get_permalink($query->posts[0]->ID);
                // Cache for 24 hours
                wp_cache_set($cache_key, $url, 'sgu_urls', DAY_IN_SECONDS);
                return $url;
            }

            // Default fallback
            $default_url = '/';
            wp_cache_set($cache_key, $default_url, 'sgu_urls', DAY_IN_SECONDS);

            return $default_url;
        }

        /** 
         * get_the_single_url
         * 
         * Get the URL for a single photo journal post
         * 
         * @since 8.4
         * @access public
         * @static
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $slug The post slug
         * @return string The URL to the single journal post
         * 
         */
        public static function get_the_single_url(string $shortcode, string $slug): string
        {

            $base_url = self::get_archive_url($shortcode);

            // If base is just '/', return it as is
            if ($base_url === '/') {
                return $base_url;
            }

            // Build the proper URL
            return rtrim($base_url, '/') . '/' . $slug . '/';
        }

        /**
         * get_weather_emoji
         * 
         * Get weather emoji based on icon code
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param string $icon Icon code (e.g., '01d', '10n')
         * @return string Weather emoji
         */
        public static function get_weather_emoji(string $icon): string
        {
            $code = substr($icon, 0, 2);
            $is_night = substr($icon, -1) === 'n';

            $emojis = [
                '01' => $is_night ? '🌙' : '☀️',
                '02' => $is_night ? '🌙' : '⛅',
                '03' => '☁️',
                '04' => '☁️',
                '09' => '🌧️',
                '10' => '🌧️',
                '11' => '⛈️',
                '13' => '❄️',
                '50' => '🌫️',
            ];

            return $emojis[$code] ?? '🌡️';
        }

        /**
         * get_weather_icon_name
         * 
         * Get weather icon name for Basmilius weather icons
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param string $icon Icon code (e.g., '01d', '10n')
         * @return string Icon name for SVG lookup
         */
        public static function get_weather_icon_name(string $icon): string
        {
            $code = substr($icon, 0, 2);
            $is_night = substr($icon, -1) === 'n';
            $suffix = $is_night ? '-night' : '-day';

            $icons = [
                '01' => 'clear' . $suffix,
                '02' => 'partly-cloudy' . $suffix,
                '03' => 'cloudy',
                '04' => 'overcast',
                '09' => 'rain',
                '10' => 'rain',
                '11' => 'thunderstorms',
                '13' => 'snow',
                '50' => 'fog',
            ];

            return $icons[$code] ?? 'not-available';
        }

        /**
         * get_weather_icon_url
         * 
         * Get full URL for weather icon SVG
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param string $icon Icon code (e.g., '01d', '10n')
         * @return string Full URL to SVG icon
         */
        public static function get_weather_icon_url(string $icon): string
        {
            $icon_name = self::get_weather_icon_name($icon);
            return 'https://raw.githubusercontent.com/basmilius/weather-icons/dev/production/fill/all/' . $icon_name . '.svg';
        }

        /**
         * wmo_code_to_description
         * 
         * Convert WMO weather code to human-readable description
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param int $code WMO weather code
         * @return string Weather description
         */
        public static function wmo_code_to_description(int $code): string
        {
            $descriptions = [
                0 => 'Clear sky',
                1 => 'Mainly clear',
                2 => 'Partly cloudy',
                3 => 'Overcast',
                45 => 'Fog',
                48 => 'Depositing rime fog',
                51 => 'Light drizzle',
                53 => 'Moderate drizzle',
                55 => 'Dense drizzle',
                56 => 'Light freezing drizzle',
                57 => 'Dense freezing drizzle',
                61 => 'Slight rain',
                63 => 'Moderate rain',
                65 => 'Heavy rain',
                66 => 'Light freezing rain',
                67 => 'Heavy freezing rain',
                71 => 'Slight snow fall',
                73 => 'Moderate snow fall',
                75 => 'Heavy snow fall',
                77 => 'Snow grains',
                80 => 'Slight rain showers',
                81 => 'Moderate rain showers',
                82 => 'Violent rain showers',
                85 => 'Slight snow showers',
                86 => 'Heavy snow showers',
                95 => 'Thunderstorm',
                96 => 'Thunderstorm with slight hail',
                99 => 'Thunderstorm with heavy hail',
            ];

            return $descriptions[$code] ?? 'Unknown';
        }

        /**
         * wmo_code_to_icon
         * 
         * Convert WMO weather code to icon code
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param int $code WMO weather code
         * @param int $is_day Whether it's daytime (1) or night (0)
         * @return string Icon code
         */
        public static function wmo_code_to_icon(int $code, int $is_day = 1): string
        {
            $time = $is_day ? 'd' : 'n';

            $icon_map = [
                0 => '01',
                1 => '01',
                2 => '02',
                3 => '04',
                45 => '50',
                48 => '50',
                51 => '09',
                53 => '09',
                55 => '09',
                56 => '09',
                57 => '09',
                61 => '10',
                63 => '10',
                65 => '10',
                66 => '13',
                67 => '13',
                71 => '13',
                73 => '13',
                75 => '13',
                77 => '13',
                80 => '09',
                81 => '09',
                82 => '09',
                85 => '13',
                86 => '13',
                95 => '11',
                96 => '11',
                99 => '11',
            ];

            $icon_code = $icon_map[$code] ?? '01';
            return $icon_code . $time;
        }

        /**
         * wind_direction_to_compass
         * 
         * Convert wind direction degrees to compass direction
         * 
         * @since 8.4
         * @access public
         * @static
         * 
         * @param float $degrees Wind direction in degrees
         * @return string Compass direction (N, NNE, NE, etc.)
         */
        public static function wind_direction_to_compass(float $degrees): string
        {
            $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
            return $directions[round($degrees / 22.5) % 16];
        }

        /**
         * Convert compass direction to degrees
         * 
         * @param string $compass Compass direction (N, NNE, NE, etc.)
         * @return int Degrees (0-360)
         */
        public static function compass_to_degrees(string $compass): int
        {
            $directions = [
                'N'   => 0,
                'NNE' => 22,
                'NE'  => 45,
                'ENE' => 67,
                'E'   => 90,
                'ESE' => 112,
                'SE'  => 135,
                'SSE' => 157,
                'S'   => 180,
                'SSW' => 202,
                'SW'  => 225,
                'WSW' => 247,
                'W'   => 270,
                'WNW' => 292,
                'NW'  => 315,
                'NNW' => 337,
            ];

            return $directions[strtoupper($compass)] ?? 0;
        }

        /**
         * Generate planet SVG icon
         * 
         * @param string $planet Planet name (mercury, venus, earth, mars, jupiter, saturn, uranus, neptune, pluto)
         * @param string $size CSS class for size (default: w-8 h-8)
         * @return string SVG icon HTML
         */
        public static function get_planet_icon($planet = 'earth', $size = 'w-8 h-8')
        {
            $class = esc_attr($size) . ' inline-block';

            return match (strtolower($planet)) {
                'mercury' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#8C7853" stroke="#6B5D47" stroke-width="2"/>
                        <circle cx="35" cy="35" r="8" fill="#6B5D47" opacity="0.6"/>
                        <circle cx="60" cy="45" r="6" fill="#6B5D47" opacity="0.5"/>
                        <circle cx="45" cy="65" r="10" fill="#6B5D47" opacity="0.7"/>
                        <circle cx="70" cy="30" r="5" fill="#6B5D47" opacity="0.4"/>
                    </svg>
                    SVG,

                'venus' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#FFC649" stroke="#E5B041" stroke-width="2"/>
                        <ellipse cx="50" cy="50" rx="35" ry="30" fill="#FFD873" opacity="0.3"/>
                        <path d="M30 40 Q50 45 70 40" stroke="#E5B041" stroke-width="1.5" fill="none" opacity="0.4"/>
                        <path d="M25 60 Q50 55 75 60" stroke="#E5B041" stroke-width="1.5" fill="none" opacity="0.4"/>
                    </svg>
                    SVG,

                'earth' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#4A90E2" stroke="#357ABD" stroke-width="2"/>
                        <path d="M30 35 Q40 30 50 35 T70 35" fill="#2D5F3F" opacity="0.8"/>
                        <path d="M25 55 Q35 50 45 55 T65 55" fill="#2D5F3F" opacity="0.8"/>
                        <ellipse cx="60" cy="70" rx="15" ry="10" fill="#2D5F3F" opacity="0.8"/>
                        <circle cx="50" cy="50" r="45" fill="white" opacity="0.15"/>
                    </svg>
                    SVG,

                'mars' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#CD5C5C" stroke="#A64545" stroke-width="2"/>
                        <circle cx="40" cy="40" r="10" fill="#A64545" opacity="0.5"/>
                        <circle cx="65" cy="55" r="8" fill="#A64545" opacity="0.6"/>
                        <circle cx="45" cy="70" r="6" fill="#A64545" opacity="0.4"/>
                        <path d="M20 50 L30 45 L35 55 Z" fill="#8B3A3A" opacity="0.7"/>
                    </svg>
                    SVG,

                'jupiter' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#D4A574" stroke="#B8956A" stroke-width="2"/>
                        <ellipse cx="50" cy="35" rx="40" ry="8" fill="#C19A6B" opacity="0.6"/>
                        <ellipse cx="50" cy="50" rx="40" ry="8" fill="#B8956A" opacity="0.7"/>
                        <ellipse cx="50" cy="65" rx="40" ry="8" fill="#A67C52" opacity="0.6"/>
                        <circle cx="30" cy="50" r="12" fill="#8B4513" opacity="0.5"/>
                    </svg>
                    SVG,

                'saturn' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="50" cy="50" rx="60" ry="10" fill="#D4C5A9" opacity="0.6" stroke="#B8A98C" stroke-width="1.5"/>
                        <ellipse cx="50" cy="50" rx="50" ry="8" fill="#E8D7BA" opacity="0.4"/>
                        <circle cx="50" cy="50" r="30" fill="#F4E7D2" stroke="#D4C5A9" stroke-width="2"/>
                        <ellipse cx="50" cy="45" rx="25" ry="5" fill="#E8D7BA" opacity="0.4"/>
                        <ellipse cx="50" cy="55" rx="25" ry="5" fill="#D4C5A9" opacity="0.3"/>
                    </svg>
                    SVG,

                'uranus' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#4FD0E7" stroke="#3BB5CC" stroke-width="2"/>
                        <circle cx="50" cy="50" r="45" fill="white" opacity="0.1"/>
                        <ellipse cx="50" cy="40" rx="35" ry="6" fill="#6FE0F7" opacity="0.4"/>
                        <ellipse cx="50" cy="60" rx="35" ry="6" fill="#2FA5BC" opacity="0.4"/>
                        <circle cx="50" cy="50" r="20" fill="#87E8F8" opacity="0.3"/>
                    </svg>
                    SVG,

                'neptune' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#4169E1" stroke="#2F4FBF" stroke-width="2"/>
                        <circle cx="50" cy="50" r="45" fill="white" opacity="0.15"/>
                        <ellipse cx="50" cy="35" rx="30" ry="8" fill="#5A7FE6" opacity="0.5"/>
                        <ellipse cx="50" cy="55" rx="30" ry="8" fill="#2F4FBF" opacity="0.5"/>
                        <circle cx="35" cy="45" r="8" fill="#6A8FFF" opacity="0.4"/>
                    </svg>
                    SVG,

                'pluto' => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="40" fill="#A89881" stroke="#8C7D6B" stroke-width="2"/>
                        <circle cx="35" cy="40" r="7" fill="#6B5D47" opacity="0.6"/>
                        <circle cx="60" cy="35" r="5" fill="#6B5D47" opacity="0.5"/>
                        <circle cx="45" cy="60" r="9" fill="#6B5D47" opacity="0.7"/>
                        <path d="M55 55 L65 65 M65 55 L55 65" stroke="#6B5D47" stroke-width="2" opacity="0.4"/>
                    </svg>
                    SVG,

                default => <<<SVG
                    <svg class="{$class}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="#4A90E2" stroke="#357ABD" stroke-width="2"/>
                        <path d="M30 35 Q40 30 50 35 T70 35" fill="#2D5F3F" opacity="0.8"/>
                        <path d="M25 55 Q35 50 45 55 T65 55" fill="#2D5F3F" opacity="0.8"/>
                        <ellipse cx="60" cy="70" rx="15" ry="10" fill="#2D5F3F" opacity="0.8"/>
                        <circle cx="50" cy="50" r="45" fill="white" opacity="0.15"/>
                    </svg>
                    SVG,
            };
        }

        /**
         * Generate moon phase SVG icon
         * 
         * @param string $phase Moon phase name (new, waxing_crescent, first_quarter, waxing_gibbous, full, waning_gibbous, last_quarter, waning_crescent)
         * @param string $size pixel size
         * @return string SVG icon HTML
         */
        public static function get_moon_phase_icon(string $phase = 'full', int $size = 100)
        {
            $phase = strtolower(str_replace(' ', '_', $phase));
            $r = $size / 2;
            $s = $size + 5;
            $cx = $r;
            $cy = $r;

            // Theme colors from Stargazers
            $lit = '#0e3759';      // astro-light - illuminated portion
            $shadow = '#77aad4';   // astro-dark - shadow portion
            $border = '#185e98';   // astro-mid - border

            $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$s}\" height=\"{$s}\" viewBox=\"0 0 {$size} {$size}\">";

            // Background circle (illuminated)
            $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"{$lit}\" stroke=\"{$border}\" stroke-width=\"0\"/>";

            // Shadow overlay based on phase
            $shadow_path = match ($phase) {
                'new', 'new_moon' => "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"" . ($r - 1) . "\" fill=\"{$shadow}\"/>",
                'waxing_crescent' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 0 {$cx} " . ($size - 1) . " A " . ($r * 0.6) . " " . ($r - 1) . " 0 1 1 {$cx} 1\" fill=\"{$shadow}\"/>",
                'first_quarter' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 0 {$cx} " . ($size - 1) . " L {$cx} 1\" fill=\"{$shadow}\"/>",
                'waxing_gibbous' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 0 {$cx} " . ($size - 1) . " A " . ($r * 0.6) . " " . ($r - 1) . " 0 1 0 {$cx} 1\" fill=\"{$shadow}\"/>",
                'full', 'full_moon' => '',
                'waning_gibbous' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 1 {$cx} " . ($size - 1) . " A " . ($r * 0.6) . " " . ($r - 1) . " 0 1 1 {$cx} 1\" fill=\"{$shadow}\"/>",
                'last_quarter' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 1 {$cx} " . ($size - 1) . " L {$cx} 1\" fill=\"{$shadow}\"/>",
                'waning_crescent' => "<path d=\"M {$cx} 1 A " . ($r - 1) . " " . ($r - 1) . " 0 1 1 {$cx} " . ($size - 1) . " A " . ($r * 0.6) . " " . ($r - 1) . " 0 1 0 {$cx} 1\" fill=\"{$shadow}\"/>",
                default => throw new InvalidArgumentException("Invalid phase: {$phase}"),
            };

            $svg .= $shadow_path;
            $svg .= '</svg>';

            return $svg;
        }
    }
}

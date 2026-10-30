<?php

/** 
 * Sync Class
 * 
 * This class will contain the api sync methods including historical syncing
 * 
 * @since 8.4
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Stargazers Plugin
 * 
 */

// We don't want to allow direct access to this
defined('ABSPATH') || die('No direct script access allowed');

if (! class_exists('SGU_Sync')) {

    /** 
     * Class SGU_Sync
     * 
     * @since 8.4
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Stargazers Plugin
     * 
     */
    class SGU_Sync
    {

        private array $sync_types;
        private ?SGU_Space_Data_CRUD $space_data;
        private ?SGU_Space_Requests $space_requests;
        private ?SGU_Space_Imagery $space_imagery;

        /** @var int Request counter for rate limiting */
        private int $request_count = 0;

        /** @var int Hour start timestamp */
        private int $hour_start = 0;

        /** @var int Requests per hour limit */
        private const RATE_LIMIT = 1000;

        /** @var int Requests per batch before pause */
        private const BATCH_SIZE = 50;

        /** @var int Seconds to pause between batches */
        private const BATCH_PAUSE = 5;

        /** @var int Days per API request (max 100 per NASA docs) */
        private const DAYS_PER_REQUEST = 5;

        /** @var string NASA Science APOD feed, used when the endpoint setting is not a feed */
        private const APOD_FEED_URL = 'https://science.nasa.gov/feed/apod-basic/';

        public function __init(): void {}

        public function __construct()
        {
            $this->space_data = new SGU_Space_Data_CRUD();
            $this->space_requests = new SGU_Space_Requests();
            $this->space_imagery = new SGU_Space_Imagery();
            $this->hour_start = time();

            $this->sync_types = [
                'geo' => 'Geomagnetic Alerts',
                'sf' => 'Solar Flare Alerts',
                'sw' => 'Space Weather Alerts',
                'cme' => 'CME Alerts',
                'neo' => 'Near Earth Objects',
                'apod' => 'Astronomy Photo of the Day',
            ];
        }

        public function __destruct()
        {
            unset($this->space_data, $this->space_requests, $this->space_imagery, $this->sync_types);
        }

        /**
         * sync_both
         * 
         * Sync both data and imagery in one command
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        public function sync_both(): void
        {
            $this->cli_line(null, WP_CLI::colorize("%YStarting combined data and imagery sync...%N"));

            $this->sync_the_data();
            $this->sync_the_imagery();

            $this->cli_line(WP_CLI::colorize("%GAll syncing complete!%N"));
        }

        /**
         * sync_the_data
         * 
         * Pulls together all syncing methods for the api data
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        public function sync_the_data(): void
        {
            $this->cli_line(null, WP_CLI::colorize("%YStarting the data sync...%N"));

            $successes = 0;
            $skipped = 0;

            foreach ($this->sync_types as $type => $display_name) {
                WP_CLI::line(WP_CLI::colorize("%CProcessing: $display_name%N"));

                $synced = $this->space_requests->process_sync_data($type);

                if ($synced) {
                    $successes++;
                    WP_CLI::success("$display_name synced successfully");
                } else {
                    $skipped++;
                }
            }

            $total = $successes + $skipped;
            $this->cli_line(WP_CLI::colorize("%GData sync completed!%N"));
            WP_CLI::line("  - Processed: $total type(s)");
            WP_CLI::line("  - Synced: $successes");
            if ($skipped > 0) {
                WP_CLI::line("  - Skipped: $skipped (no updates needed)");
            }
        }

        /**
         * sync_the_imagery
         * 
         * Pulls together all syncing methods for the api imagery
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        public function sync_the_imagery(): void
        {
            $this->cli_line(null, WP_CLI::colorize("%YStarting the imagery sync...%N"));

            WP_CLI::line(WP_CLI::colorize("%CProcessing: APOD Imagery%N"));

            try {
                $this->space_imagery->sync_apod_imagery();
                WP_CLI::success("APOD imagery synced successfully");
            } catch (Exception $e) {
                WP_CLI::error("Failed to sync APOD imagery: " . $e->getMessage(), false);
            }

            $this->cli_line(WP_CLI::colorize("%GImagery sync completed!%N"));
        }

        /**
         * perform_cleanup
         * 
         * Perform post-sync cleanup operations
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        public function perform_cleanup(): void
        {
            WP_CLI::line(WP_CLI::colorize("%YPerforming post-sync cleanup...%N"));

            WP_CLI::line("  - Removing duplicates...");
            $removed = $this->space_data->clean_up();
            WP_CLI::line("    Removed $removed duplicate post(s)");

            WP_CLI::line("  - Optimizing database...");
            WP_CLI::runcommand('db optimize', ['return' => 'all', 'launch' => false, 'exit_error' => false]);

            WP_CLI::success("Cleanup completed");
        }

        /**
         * sync_historical_apod
         * 
         * Sync all historical APOD data from a start date
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $start_date Start date in Y-m-d format (default: 2015-01-01)
         * @param string|null $end_date End date in Y-m-d format (default: today)
         * 
         * @return void
         */
        public function sync_historical_apod(string $start_date = '2015-01-01', ?string $end_date = null): void
        {

            if ($end_date === null) {
                $end_date = date('Y-m-d');
            }

            $this->cli_line(null, WP_CLI::colorize("%YStarting historical APOD sync...%N"));
            WP_CLI::line(sprintf("Date range: %s to %s", $start_date, $end_date));

            $keys = SGU_Static::get_api_key('apod');
            if (empty($keys)) {
                WP_CLI::error("No API keys configured. Please set up NASA API keys in CME settings.");
                return;
            }

            WP_CLI::line(sprintf("Using %d API key(s)", count($keys)));

            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $total_days = $start->diff($end)->days + 1;

            WP_CLI::line(sprintf("Total days to sync: %d", $total_days));
            WP_CLI::line("");

            $current_start = clone $start;
            $inserted = 0;
            $skipped = 0;
            $failed = 0;
            $batch_count = 0;

            $progress = \WP_CLI\Utils\make_progress_bar('Syncing APOD data', $total_days);

            while ($current_start <= $end) {

                $chunk_end = clone $current_start;
                $chunk_end->modify('+' . (self::DAYS_PER_REQUEST - 1) . ' days');

                if ($chunk_end > $end) {
                    $chunk_end = clone $end;
                }

                $this->check_rate_limit();

                $api_key = $keys[array_rand($keys)];

                $url = sprintf(
                    'https://api.nasa.gov/planetary/apod?api_key=%s&start_date=%s&end_date=%s',
                    $api_key['key'],
                    $current_start->format('Y-m-d'),
                    $chunk_end->format('Y-m-d')
                );

                WP_CLI::line(sprintf("Day Syncing: %d", $this->request_count + 1));

                $response = $this->make_api_request($url);
                $this->request_count++;

                if ($response && is_array($response)) {

                    foreach ($response as $apod) {

                        $result = $this->insert_apod($apod);

                        if ($result === 'inserted') {
                            $inserted++;
                        } elseif ($result === 'skipped') {
                            $skipped++;
                        } else {
                            $failed++;
                        }

                        $progress->tick();
                    }
                } else {

                    // API failed - fallback to archive scraping
                    $response = $this->scrape_apod_archive(
                        $current_start->format('Y-m-d'),
                        $chunk_end->format('Y-m-d')
                    );

                    if ($response && is_array($response)) {
                        foreach ($response as $apod) {
                            $result = $this->insert_apod($apod);

                            if ($result === 'inserted') {
                                $inserted++;
                            } elseif ($result === 'skipped') {
                                $skipped++;
                            } else {
                                $failed++;
                            }
                            $progress->tick();
                        }
                    } else {
                        $days_in_chunk = $current_start->diff($chunk_end)->days + 1;
                        $failed += $days_in_chunk;

                        for ($i = 0; $i < $days_in_chunk; $i++) {
                            $progress->tick();
                        }

                        WP_CLI::warning(sprintf(
                            "Failed to fetch data for %s to %s",
                            $current_start->format('Y-m-d'),
                            $chunk_end->format('Y-m-d')
                        ));
                    }
                }

                $current_start = clone $chunk_end;
                $current_start->modify('+1 day');

                $batch_count++;
                if ($batch_count >= self::BATCH_SIZE) {
                    sleep(self::BATCH_PAUSE);
                    $batch_count = 0;
                }
            }

            $progress->finish();

            $this->cli_line(null, null);
            WP_CLI::success("Historical APOD sync completed!");
            WP_CLI::line(sprintf("  - Inserted: %d", $inserted));
            WP_CLI::line(sprintf("  - Skipped (already exist): %d", $skipped));
            if ($failed > 0) {
                WP_CLI::line(WP_CLI::colorize(sprintf("  - %%RFailed: %d%%N", $failed)));
            }
            WP_CLI::line(sprintf("  - API requests made: %d", $this->request_count));
        }

        /**
         * sync_historical_imagery
         * 
         * Sync all missing APOD imagery
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        public function sync_historical_imagery(): void
        {

            $this->cli_line(null, WP_CLI::colorize("%YStarting historical imagery sync...%N"));

            $args = [
                'post_type' => 'sgu_apod',
                'post_status' => ['publish', 'future'],
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'sgu_apod_local_media_type',
                        'value' => 'image',
                        'compare' => '=',
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key' => 'sgu_apod_local_media',
                            'value' => '',
                            'compare' => '=',
                        ],
                        [
                            'key' => 'sgu_apod_local_media',
                            'compare' => 'NOT EXISTS',
                        ],
                    ],
                ],
                'fields' => 'ids',
            ];

            $query = new WP_Query($args);
            $total = $query->found_posts;

            if ($total === 0) {
                WP_CLI::success("All images are already synced!");
                return;
            }

            WP_CLI::line(sprintf("Found %d images to download", $total));
            WP_CLI::line("");

            $this->space_imagery->sync_apod_imagery_with_progress();

            $this->cli_line(null, null);
            WP_CLI::success("Historical imagery sync completed!");
        }

        /**
         * sync_both_historical
         * 
         * Sync both historical data and imagery
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $start_date Start date
         * @param string|null $end_date End date
         * 
         * @return void
         */
        public function sync_both_historical(string $start_date = '2015-01-01', ?string $end_date = null): void
        {

            $this->cli_line(null, WP_CLI::colorize("%YStarting combined historical sync...%N"));

            $this->sync_historical_apod($start_date, $end_date);

            WP_CLI::line("");

            $this->sync_historical_imagery();

            $this->cli_line(WP_CLI::colorize("%GAll historical syncing complete!%N"), null);
        }

        /**
         * insert_apod
         * 
         * Insert a single APOD record
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param array $data APOD data from API
         * 
         * @return string 'inserted', 'skipped', or 'failed'
         */
        private function insert_apod(array $data): string
        {

            if (empty($data) || empty($data['title'])) {
                return 'failed';
            }

            $title = sanitize_text_field($data['title']);
            $date = sanitize_text_field(date('Y-m-d H:i:s', strtotime($data['date'] ?? 'now')));
            $content = sanitize_text_field($data['explanation'] ?? '');

            $media = sanitize_url($data['hdurl'] ?? $data['url'] ?? '');

            $copyright = sanitize_text_field($data['copyright'] ?? 'NASA/JPL');

            $media_type = sanitize_text_field($data['media_type'] ?? 'image');

            $existing_id = SGU_Static::get_id_from_slug(sanitize_title($title), 'sgu_apod');

            $args = [
                'post_status' => 'publish',
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'sgu_apod',
                'post_author' => 16,
                'post_date' => $date,
            ];

            // it doesnt already exist
            if ($existing_id == 0) {
                $existing_id = wp_insert_post($args);
                // it does, so update instead
            } else {
                // Update existing post with revised data
                $args['ID'] = $existing_id;
                wp_update_post($args);
            }

            if (is_wp_error($existing_id) || $existing_id === 0) {
                return 'failed';
            }

            update_post_meta($existing_id, 'sgu_apod_local_media_type', $media_type);
            update_post_meta($existing_id, 'sgu_apod_orignal_media', $media);
            update_post_meta($existing_id, 'sgu_apod_local_media', '');
            update_post_meta($existing_id, 'sgu_apod_copyright', $copyright);

            return 'inserted';
        }

        /**
         * make_api_request
         * 
         * Execute HTTP request to NASA API
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $url Full URL to request
         * 
         * @return array|bool Parsed response or false on failure
         */
        private function make_api_request(string $url): array|bool
        {

            $args = [
                'timeout' => 30,
                'redirection' => 3,
                'user-agent' => 'US Star Gazers ( iam@kevinpirnie.com )',
            ];

            $request = wp_safe_remote_get($url, $args);

            if (is_wp_error($request)) {
                WP_CLI::error(sprintf("ERROR: %s", $request->get_error_message()), false);
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($request);

            if ($response_code === 429) {
                WP_CLI::warning("Rate limit hit, waiting 60 seconds...");
                sleep(60);
                $this->hour_start = time();
                $this->request_count = 0;
                return $this->make_api_request($url);
            }

            if ($response_code !== 200) {
                WP_CLI::error(sprintf("Invalid Response Code: %d", $response_code), false);
                return false;
            }

            $body = wp_remote_retrieve_body($request);

            if (! $body) {
                WP_CLI::error("NO BODY", false);
                return false;
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                WP_CLI::error(sprintf("JSON Decode Error: %s", json_last_error_msg()), false);
                return false;
            }

            return $data;
        }

        /**
         * scrape_apod_archive
         * 
         * Fallback method to pull APOD data from NASA Science's APOD feed,
         * then walks the "Yesterday's Image" links for anything older than the feed holds
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $start Start date in Y-m-d format
         * @param string $end End date in Y-m-d format
         * 
         * @return array Array of APOD data or empty array on failure
         * 
         */
        public function scrape_apod_archive(string $start, string $end): array
        {
            $results = [];

            // pull the feed first, it holds the most recent days
            $response = wp_safe_remote_get($this->get_apod_feed_url(), ['timeout' => 30]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return $results;
            }

            $feed = $this->parse_apod_feed(wp_remote_retrieve_body($response));
            if (empty($feed)) {
                return $results;
            }

            // keep what's in range, and track the oldest page so we can walk back from it
            $oldest = null;
            foreach ($feed as $apod) {
                if ($apod['date'] >= $start && $apod['date'] <= $end) {
                    $results[] = $apod;
                }
                if ($oldest === null || $apod['date'] < $oldest['date']) {
                    $oldest = $apod;
                }
            }

            // the feed covers the whole range, we're done
            if ($oldest['date'] <= $start) {
                return $results;
            }

            // walk back through the article pages until we're past the start date
            $page_url = $this->get_apod_prev_url($oldest['page']);

            while ($page_url) {

                $response = wp_safe_remote_get($page_url, ['timeout' => 30]);
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    break;
                }

                $apod = $this->parse_apod_page(wp_remote_retrieve_body($response));
                if (empty($apod) || $apod['date'] < $start) {
                    break;
                }

                if ($apod['date'] <= $end && ! empty($apod['title']) && ! empty($apod['url'])) {
                    $results[] = $apod;
                }

                $page_url = $apod['prev'];
                usleep(100000); // 0.1s delay between requests
            }

            return $results;
        }

        /**
         * parse_apod_feed
         * 
         * Parse NASA Science's APOD RSS feed into APOD data arrays
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $xml The raw feed XML
         * 
         * @return array Array of APOD data or empty array on failure
         * 
         */
        public function parse_apod_feed(string $xml): array
        {
            $results = [];

            $prev = libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            libxml_use_internal_errors($prev);

            if (! $rss || ! isset($rss->channel->item)) {
                return $results;
            }

            foreach ($rss->channel->item as $item) {

                $apod = $item->children('https://science.nasa.gov/apod/');
                $content = (string) $item->children('http://purl.org/rss/1.0/modules/content/')->encoded;

                $title = html_entity_decode((string) $item->title, ENT_QUOTES | ENT_HTML5);
                $hdurl = html_entity_decode((string) $apod->hdurl, ENT_QUOTES | ENT_HTML5);

                // youtube embeds are the only videos we keep, everything else is the image or the video's poster
                $media = $this->get_apod_youtube_url($content);
                $media_type = $media ? 'video' : 'image';
                if (! $media) {
                    $media = $this->get_apod_original_image($hdurl);
                }

                if (! $title || ! $media) {
                    continue;
                }

                $results[] = [
                    'date' => wp_date('Y-m-d', strtotime((string) $item->pubDate), new DateTimeZone('America/New_York')),
                    'title' => $title,
                    'explanation' => $this->clean_apod_explanation((string) $apod->explanation),
                    'url' => $media,
                    'hdurl' => $media,
                    'media_type' => $media_type,
                    'copyright' => $this->clean_apod_text((string) $apod->copyright) ?: 'NASA/JPL',
                    'page' => (string) $item->link,
                ];
            }

            return $results;
        }

        /**
         * parse_apod_page
         * 
         * Parse a single NASA Science APOD article page into APOD data
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $html The raw page HTML
         * 
         * @return array APOD data, including the previous day's page url, or empty array on failure
         * 
         */
        private function parse_apod_page(string $html): array
        {

            if (! preg_match('/<meta property="article:published_time" content="(\d{4}-\d{2}-\d{2})/i', $html, $date_match)) {
                return [];
            }

            // Extract title
            preg_match('/<h1 class="display-48[^"]*">\s*(.+?)\s*<\/h1>/is', $html, $title_match);
            $title = isset($title_match[1]) ? $this->clean_apod_text($title_match[1]) : '';

            // the media lives in the hero, above the title
            $hero = isset($title_match[0]) ? substr($html, 0, strpos($html, $title_match[0])) : $html;

            // Extract media url
            $media_url = $this->get_apod_youtube_url($hero);
            $media_type = $media_url ? 'video' : 'image';
            if (! $media_url) {
                if (preg_match('/href="(https:\/\/assets\.science\.nasa\.gov\/content\/dam\/[^"]+)"/i', $hero, $img_match)) {
                    $media_url = $this->get_apod_original_image($img_match[1]);
                } elseif (preg_match('/<video[^>]+poster="([^"]+)"/i', $hero, $img_match)) {
                    $media_url = $this->get_apod_original_image($img_match[1]);
                } elseif (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $img_match)) {
                    $media_url = $this->get_apod_original_image($img_match[1]);
                }
            }

            // Extract explanation
            preg_match('/<p class="[^"]*media-detail-hero__description[^"]*">(.+?)<\/p>/is', $html, $exp_match);

            // Extract copyright
            preg_match('/<th[^>]*>\s*[^<]*Credit[^<]*<\/th>\s*<td[^>]*>(.+?)<\/td>/is', $html, $cr_match);

            return [
                'date' => $date_match[1],
                'title' => $title,
                'explanation' => isset($exp_match[1]) ? $this->clean_apod_explanation($exp_match[1]) : '',
                'url' => $media_url,
                'hdurl' => $media_url,
                'media_type' => $media_type,
                'copyright' => (isset($cr_match[1]) ? $this->clean_apod_text($cr_match[1]) : '') ?: 'NASA/JPL',
                'prev' => $this->get_apod_prev_url('', $html),
            ];
        }

        /**
         * get_apod_prev_url
         * 
         * Get the "Yesterday's Image" url from an APOD article page
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $page_url The article page url to pull, if the html is not passed
         * @param string $html The article page html, if already pulled
         * 
         * @return string The previous day's page url, or empty string if not found
         * 
         */
        private function get_apod_prev_url(string $page_url, string $html = ''): string
        {

            if (! $html && $page_url) {
                $response = wp_safe_remote_get($page_url, ['timeout' => 30]);
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    return '';
                }
                $html = wp_remote_retrieve_body($response);
            }

            if (preg_match('/<a class="smd-next-prev__prev" href="([^"]+)"/i', $html, $prev_match)) {
                return html_entity_decode($prev_match[1], ENT_QUOTES | ENT_HTML5);
            }

            return '';
        }

        /**
         * get_apod_youtube_url
         * 
         * Get the youtube embed url from APOD markup
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $html The markup to search
         * 
         * @return string The youtube embed url, or empty string if not found
         * 
         */
        private function get_apod_youtube_url(string $html): string
        {

            if (preg_match('/youtube(?:-nocookie)?\.com\/embed\/([A-Za-z0-9_-]+)/i', $html, $vid_match)) {
                return sprintf('https://www.youtube.com/embed/%s', $vid_match[1]);
            }

            return '';
        }

        /**
         * get_apod_original_image
         * 
         * Convert a NASA Science resized or rendition image url to the original
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $url The image url
         * 
         * @return string The original image url
         * 
         */
        private function get_apod_original_image(string $url): string
        {

            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5);
            $url = strtok($url, '?') ?: '';
            $url = str_replace('/dynamicimage/assets/science/', '/content/dam/science/', $url);
            $url = preg_replace('/\/jcr:content\/.*$/i', '', $url);

            return $url;
        }

        /**
         * clean_apod_explanation
         * 
         * Strip the explanation label and the tomorrow's picture teaser from the explanation
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $html The explanation markup
         * 
         * @return string The plain text explanation
         * 
         */
        private function clean_apod_explanation(string $html): string
        {

            $html = preg_replace('/<strong>\s*Tomorrow.+$/is', '', $html);
            $text = $this->clean_apod_text($html);

            return trim(preg_replace('/^Explanation:\s*/i', '', $text));
        }

        /**
         * clean_apod_text
         * 
         * Strip tags, decode entities, and collapse whitespace
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string $html The markup to clean
         * 
         * @return string The plain text
         * 
         */
        private function clean_apod_text(string $html): string
        {

            $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES | ENT_HTML5);

            return trim(preg_replace('/\s+/u', ' ', $text));
        }

        /**
         * get_apod_feed_url
         * 
         * Get the APOD feed url from the endpoint setting, falling back to NASA Science's feed
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return string The feed url
         * 
         */
        private function get_apod_feed_url(): string
        {

            $endpoint = SGU_Static::get_api_endpoint('apod')[0] ?? '';

            if ($endpoint && ! str_contains($endpoint, 'api.nasa.gov')) {
                return $endpoint;
            }

            return self::APOD_FEED_URL;
        }

        /**
         * check_rate_limit
         * 
         * Check and handle rate limiting
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @return void
         */
        private function check_rate_limit(): void
        {

            if ($this->request_count >= self::RATE_LIMIT) {

                $elapsed = time() - $this->hour_start;

                if ($elapsed < 3600) {
                    $wait_time = 3600 - $elapsed + 10;
                    WP_CLI::warning(sprintf("Rate limit approaching, waiting %d seconds...", $wait_time));
                    sleep($wait_time);
                }

                $this->hour_start = time();
                $this->request_count = 0;
            }
        }

        /**
         * cli_line
         * 
         * Display a separator line with optional before/after messages
         * 
         * @since 8.4
         * @access private
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package US Stargazers Plugin
         * 
         * @param string|null $before Optional message before line
         * @param string|null $after Optional message after line
         * 
         * @return void
         */
        private function cli_line(?string $before = null, ?string $after = null): void
        {
            if ($before) {
                WP_CLI::line($before);
            }

            WP_CLI::line(WP_CLI::colorize('%B' . str_repeat('*', 76) . '%n'));

            if ($after) {
                WP_CLI::line($after);
            }
        }
    }
}

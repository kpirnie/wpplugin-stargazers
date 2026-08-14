<?php
/** 
 * Space Imagery
 * 
 * This file contains methods for downloading and managing astronomy imagery.
 * Handles downloading images from external APIs (primarily NASA) and storing
 * them in the WordPress media library for local serving.
 * 
 * @since 8.0
 * @author Kevin Pirnie <me@kpirnie.com>
 * @package US Star Gazers
 * 
*/

// We don't want to allow direct access to this
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// make sure this class does not already exist
if( ! class_exists( 'SGU_Space_Imagery' ) ) {

    /** 
     * Class SGU_Space_Imagery
     * 
     * Manages downloading and storing astronomy images locally.
     * 
     * Key responsibilities:
     * - Download images from NASA URLs
     * - Upload to WordPress media library
     * - Update post meta with local URLs
     * - Skip already-downloaded images
     * - Handle both images and videos appropriately
     * 
     * @since 8.0
     * @access public
     * @author Kevin Pirnie <me@kpirnie.com>
     * @package US Star Gazers
     * 
    */
    class SGU_Space_Imagery {

        /** 
         * sync_apod_imagery
         * 
         * Downloads APOD images from NASA and stores them locally.
         * Only processes posts that don't have local copies yet.
         * 
         * Process:
         * 1. Query all APOD posts
         * 2. For each post:
         *    - Check if local copy already exists
         *    - If not, download from NASA
         *    - Upload to WordPress media library
         *    - Update post meta with local URL
         * 
         * Images only - videos remain as external embeds.
         * 
         * @since 8.0
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @return void This method returns nothing
         * 
        */
        public function sync_apod_imagery( ) : void {

            // Query all published APOD posts
            $args = [
                'post_type' => 'sgu_apod',
                'post_status' => ['publish', 'future'],
                'posts_per_page' => -1  // Get all posts
            ];

            $qry = new WP_Query( $args );
            $rs = $qry -> get_posts( );

            // Early return if no posts found
            if( ! $rs ) {
                return;
            }

            // Loop through each APOD post
            foreach( $rs as $post ) {

                // Extract post ID and meta data
                $id = $post -> ID;
                $post_meta = get_post_meta( $id );
                
                // Get media URLs and type from post meta
                $orig_media = $post_meta['sgu_apod_orignal_media'][0] ?? null;
                $local_media = $post_meta['sgu_apod_local_media'][0] ?? null;
                $media_type = $post_meta['sgu_apod_local_media_type'][0] ?? null;
                
                // Check if we need to download this image
                // TRUE if local_media is empty or null
                $does_not_have_local = empty( $local_media ) || is_null( $local_media );

                // Only process if:
                // 1. We have an original URL from NASA
                // 2. No local copy exists yet
                // 3. Media type is image (not video)
                if( $orig_media && $does_not_have_local && $media_type == 'image' ) {

                    // Skip if URL appears to be a video embed
                    if( strpos( $orig_media, 'youtube.com' ) !== false || 
                        strpos( $orig_media, 'youtu.be' ) !== false ||
                        strpos( $orig_media, 'vimeo.com' ) !== false ) {
                        continue;
                    }

                    // Clean the URL - remove %20 and other URL encoding issues
                    $clean_url = str_replace( '%20', ' ', $orig_media );
                    $clean_url = trim( $clean_url );
                    
                    // Extract filename from cleaned URL
                    $filepath = basename( wp_parse_url( $clean_url, PHP_URL_PATH ) );
                    
                    // Remove trailing spaces and %20 from filename
                    $filepath = str_replace( '%20', '', $filepath );
                    $filepath = trim( $filepath );

                    // Check if this file already exists in media library
                    // post_exists() returns post ID if title matches, 0 if not
                    $attach_id = post_exists( $filepath );

                    // If attachment already exists, just update the meta and skip download
                    if( $attach_id ) {
                        $attach_url = wp_get_attachment_url( $attach_id );
                        if( $attach_url ) {
                            update_post_meta( $id, 'sgu_apod_local_media', $attach_url );
                            continue; // Skip to next post
                        }
                    }

                    // Attachment doesn't exist, need to download
                    // Download image from NASA using WordPress HTTP API
                    // 90 second timeout for large HD images
                    $response = wp_safe_remote_get( $clean_url, ['timeout' => 90] );

                    // Check for download errors
                    if( ! is_wp_error( $response ) ) {

                        // Extract image binary data from response
                        $bits = wp_remote_retrieve_body( $response );

                        // Upload to WordPress using wp_upload_bits()
                        // This handles file storage and generates thumbnails
                        $upload = wp_upload_bits( $filepath, null, $bits );

                        // Check if upload succeeded
                        if( ! isset( $upload['error'] ) || ! $upload['error'] ) {

                            // convert tiffs to pngs
                            $converted_path = $this->convert_tif_for_upload( $upload['file'] );
                            if( $converted_path !== $upload['file'] ) {
                                $upload['file'] = $converted_path;
                                $upload['url'] = preg_replace( '/\.tiff?$/i', '.png', $upload['url'] );
                                $upload['type'] = 'image/png';
                                $filepath = preg_replace( '/\.tiff?$/i', '.png', $filepath );
                            }

                            // Prepare attachment post data
                            $attachment = [
                                'post_title' => $filepath,
                                'post_mime_type' => $upload['type'],  // Detected MIME type
                                'guid' => $upload['url']               // Permanent URL
                            ];

                            // Insert attachment into media library
                            // Returns new attachment post ID
                            $attach_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

                            // Generate attachment metadata (dimensions, thumbnails, etc.)
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                            wp_update_attachment_metadata( $attach_id, $attach_data );

                            // Update APOD post with local media URL
                            update_post_meta( $id, 'sgu_apod_local_media', $upload['url'] );

                        } else {
                            // Log upload error
                            error_log( sprintf( "Upload failed for %s: %s", $filepath, $upload['error'] ) );
                        }

                    } else {
                        // Log download error for debugging
                        error_log( sprintf( "There was an issue pulling: %s", $clean_url ) );
                        error_log( $response -> get_error_message( ) );
                    }
                }
            }
        }

        /**
         * Convert TIF/TIFF to PNG before WordPress upload
         * Add this before media_handle_sideload call
         */
        private function convert_tif_for_upload( string $file_path ) : string {
            $ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
            
            if( ! in_array( $ext, ['tif', 'tiff'], true ) ) {
                return $file_path;
            }
            
            // Check if ImageMagick is available
            if( extension_loaded( 'imagick' ) ) {
                try {
                    $imagick = new Imagick( $file_path );
                    $new_path = preg_replace( '/\.tiff?$/i', '.png', $file_path );
                    $imagick -> setImageFormat( 'png' );
                    $imagick -> writeImage( $new_path );
                    $imagick -> destroy( );
                    @unlink( $file_path );
                    return $new_path;
                } catch( Exception $e ) {
                    // Fall through to GD
                }
            }
            
            // Fallback to GD if available
            if( function_exists( 'imagecreatefromstring' ) ) {
                $img_data = @file_get_contents( $file_path );
                $img = @imagecreatefromstring( $img_data );
                if( $img ) {
                    $new_path = preg_replace( '/\.tiff?$/i', '.png', $file_path );
                    imagepng( $img, $new_path );
                    imagedestroy( $img );
                    @unlink( $file_path );
                    return $new_path;
                }
            }
            
            // Last resort: just rename to .png (may not work for all viewers)
            $new_path = preg_replace( '/\.tiff?$/i', '.png', $file_path );
            @rename( $file_path, $new_path );
            return $new_path;
        }

        /** 
         * sync_apod_imagery_with_progress
         * 
         * Downloads APOD images from NASA and stores them locally with CLI progress.
         * Only processes posts that don't have local copies yet.
         * 
         * @since 8.4
         * @access public
         * @author Kevin Pirnie <me@kpirnie.com>
         * @package Stargazers.us Theme
         * 
         * @return void This method returns nothing
         * 
        */
        public function sync_apod_imagery_with_progress( ) : void {
            
            // Add upload_mimes filter
            add_filter( 'upload_mimes', function( $mimes ) {
                $mimes['gif'] = 'image/gif';
                $mimes['svg'] = 'image/svg+xml';
                $mimes['svgz'] = 'image/svg+xml';
                $mimes['tif'] = 'image/tiff';
                $mimes['tiff'] = 'image/tiff';
                return $mimes;
            } );
            add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
                $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
                if( in_array( $ext, ['tif', 'tiff'], true ) ) {
                    $data['ext'] = $ext;
                    $data['type'] = 'image/tiff';
                    $data['proper_filename'] = $filename;
                }
                return $data;
            }, 10, 4 );

            // Query all published APOD posts that need imagery
            $args = [
                'post_type' => 'sgu_apod',
                'post_status' => [ 'publish', 'future' ],
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
            ];

            $qry = new WP_Query( $args );
            $rs = $qry -> get_posts( );

            // Early return if no posts found
            if( ! $rs ) {
                if( defined( 'WP_CLI' ) && WP_CLI ) {
                    WP_CLI::success( "No images to download." );
                }
                return;
            }

            $total = count( $rs );
            $downloaded = 0;
            $skipped = 0;
            $failed = 0;

            // Create progress bar if WP-CLI is available
            $progress = null;
            if( defined( 'WP_CLI' ) && WP_CLI ) {
                $progress = \WP_CLI\Utils\make_progress_bar( 'Downloading images', $total );
            }

            // Loop through each APOD post
            foreach( $rs as $post ) {

                // Extract post ID and meta data
                $id = $post -> ID;
                $orig_media = get_post_meta( $id, 'sgu_apod_orignal_media', true );
                $local_media = get_post_meta( $id, 'sgu_apod_local_media', true );
                $media_type = get_post_meta( $id, 'sgu_apod_local_media_type', true );

                // Check if we need to download this image
                $does_not_have_local = empty( $local_media ) || is_null( $local_media );

                if( ! $orig_media ) {
                    $skipped++;
                    if( $progress ) $progress -> tick( );
                    continue;
                }

                // Only process if we have an original URL, no local copy exists yet, and media type is image
                if( $orig_media && $does_not_have_local && $media_type == 'image' ) {

                    // Skip if URL appears to be a video embed
                    if( strpos( $orig_media, 'youtube.com' ) !== false || 
                        strpos( $orig_media, 'youtu.be' ) !== false ||
                        strpos( $orig_media, 'vimeo.com' ) !== false ) {
                        $skipped++;
                        if( $progress ) $progress -> tick( );
                        continue;
                    }

                    // Clean the URL - remove %20 and other URL encoding issues
                    $clean_url = str_replace( '%20', ' ', $orig_media );
                    $clean_url = trim( $clean_url );
                    
                    // Extract filename from cleaned URL
                    $filepath = basename( wp_parse_url( $clean_url, PHP_URL_PATH ) );
                    
                    // Remove trailing spaces and %20 from filename
                    $filepath = str_replace( '%20', '', $filepath );
                    $filepath = trim( $filepath );

                    // Check if this file already exists in media library
                    $attach_id = post_exists( $filepath );

                    // If attachment already exists, just update the meta and skip download
                    if( $attach_id ) {
                        $attach_url = wp_get_attachment_url( $attach_id );
                        if( $attach_url ) {
                            update_post_meta( $id, 'sgu_apod_local_media', $attach_url );
                            $skipped++;
                            if( $progress ) $progress -> tick( );
                            continue;
                        }
                    }

                    // Attachment doesn't exist, need to download
                    // Use the cleaned URL for download
                    $response = wp_remote_get( $clean_url, [ 
                        'timeout' => 90,
                        'sslverify' => true,
                    ] );

                    // Check for download errors
                    if( ! is_wp_error( $response ) ) {

                        // Extract image binary data from response
                        $bits = wp_remote_retrieve_body( $response );

                        // Upload to WordPress using wp_upload_bits()
                        $upload = wp_upload_bits( $filepath, null, $bits );

                        // Check if upload succeeded
                        if( ! isset( $upload['error'] ) || ! $upload['error'] ) {

                            // convert tiff images
                            $converted_path = $this->convert_tif_for_upload( $upload['file'] );
                            if( $converted_path !== $upload['file'] ) {
                                $upload['file'] = $converted_path;
                                $upload['url'] = preg_replace( '/\.tiff?$/i', '.png', $upload['url'] );
                                $upload['type'] = 'image/png';
                                $filepath = preg_replace( '/\.tiff?$/i', '.png', $filepath );
                            }

                            // Prepare attachment post data
                            $attachment = [
                                'post_title' => $filepath,
                                'post_mime_type' => $upload['type'],
                                'guid' => $upload['url'],
                            ];

                            // Insert attachment into media library
                            $attach_id = wp_insert_attachment( $attachment, $upload['file'], 0 );

                            // Generate attachment metadata
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                            wp_update_attachment_metadata( $attach_id, $attach_data );

                            // Update APOD post with local media URL
                            update_post_meta( $id, 'sgu_apod_local_media', $upload['url'] );

                            $downloaded++;

                        } else {
                            $failed++;
                            error_log( sprintf( "Upload failed for %s: %s", $filepath, $upload['error'] ) );
                        }

                    } else {
                        $failed++;
                        error_log( sprintf( "Download failed for %s: %s", $clean_url, $response -> get_error_message( ) ) );
                    }

                    if( $progress ) $progress -> tick( );

                    // Small delay to avoid overwhelming servers
                    usleep( 100000 ); // 0.1 second

                } else {
                    $skipped++;
                    if( $progress ) $progress -> tick( );
                }
            }

            if( $progress ) $progress -> finish( );

            // Summary
            if( defined( 'WP_CLI' ) && WP_CLI ) {
                WP_CLI::line( sprintf( "  - Downloaded: %d", $downloaded ) );
                WP_CLI::line( sprintf( "  - Skipped (already exist): %d", $skipped ) );
                if( $failed > 0 ) {
                    WP_CLI::line( WP_CLI::colorize( sprintf( "  - %%RFailed: %d%%N", $failed ) ) );
                }
            }

        }
        
        
    }
}
<?php
/**
 * Template: APOD Single
 * 
 * Displays a single Astronomy Picture of the Day post
 * 
 * @package US Star Gazers
 * @since 8.4
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

get_header();

while ( have_posts() ) : the_post();
    
    $post_id = get_the_ID();
    $meta = get_post_meta( $post_id );
    $media_type = $meta['sgu_apod_local_media_type'][0] ?? 'image';
    $local_media = $meta['sgu_apod_local_media'][0] ?? '';
    $original_media = $meta['sgu_apod_orignal_media'][0] ?? '';
    $copyright = $meta['sgu_apod_copyright'][0] ?? 'NASA/JPL';
    $media_url = ! empty( $local_media ) ? $local_media : $original_media;
    
    // Get archive URL for back link
    $archive_url = SGU_Static::get_archive_url( 'sgup_photo_journals' );
    
    // Get adjacent posts for navigation
    $prev_post = get_previous_post();
    $next_post = get_next_post();
?>

<main class="sgu-apod-single">
    
    <article class="sgu-apod-article" id="post-<?php the_ID(); ?>">
        
        <header class="sgu-apod-header">
            
            <nav class="sgu-apod-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'sgup' ); ?>">
                <a href="<?php echo esc_url( $archive_url ); ?>" class="sgu-breadcrumb-link">
                    <span class="sgu-breadcrumb-icon" aria-hidden="true">←</span>
                    <?php esc_html_e( 'Back to Archive', 'sgup' ); ?>
                </a>
            </nav>
            
            <time class="sgu-apod-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                <?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
            </time>
            
            <h1 class="sgu-apod-title"><?php the_title(); ?></h1>
            
            <?php if ( ! empty( $copyright ) ) : ?>
                <p class="sgu-apod-credit">
                    <span class="sgu-credit-label"><?php esc_html_e( 'Credit:', 'sgup' ); ?></span>
                    <span class="sgu-credit-value"><?php echo esc_html( $copyright ); ?></span>
                </p>
            <?php endif; ?>
            
        </header>
        
        <div class="sgu-apod-media">
            
            <?php if ( $media_type === 'image' && ! empty( $media_url ) ) : ?>
                
                <figure class="sgu-apod-figure">
                    <a href="<?php echo esc_url( $media_url ); ?>" target="_blank" rel="noopener" class="sgu-apod-image-link">
                        <img 
                            src="<?php echo esc_url( $media_url ); ?>" 
                            alt="<?php echo esc_attr( get_the_title() ); ?>"
                            class="sgu-apod-image"
                        />
                        <span class="sgu-fullsize-hint">
                            <span class="sgu-hint-icon" aria-hidden="true">🔍</span>
                            <?php esc_html_e( 'Click for full size', 'sgup' ); ?>
                        </span>
                    </a>
                </figure>
                
            <?php elseif ( $media_type === 'video' && ! empty( $media_url ) ) : ?>
                
                <div class="sgu-apod-video-container">
                    
                    <?php
                    // Check if it's a YouTube URL
                    if ( strpos( $media_url, 'youtube.com' ) !== false || strpos( $media_url, 'youtu.be' ) !== false ) :
                        
                        // Extract YouTube video ID
                        $video_id = '';
                        if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $media_url, $matches ) ) {
                            $video_id = $matches[1];
                        }
                        
                        if ( $video_id ) :
                    ?>
                        <div class="sgu-video-wrapper sgu-video-youtube">
                            <iframe 
                                src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>?rel=0" 
                                title="<?php echo esc_attr( get_the_title() ); ?>"
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    <?php 
                        endif;
                        
                    // Check if it's a Vimeo URL
                    elseif ( strpos( $media_url, 'vimeo.com' ) !== false ) :
                        
                        // Extract Vimeo video ID
                        $video_id = '';
                        if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $media_url, $matches ) ) {
                            $video_id = $matches[1];
                        }
                        
                        if ( $video_id ) :
                    ?>
                        <div class="sgu-video-wrapper sgu-video-vimeo">
                            <iframe 
                                src="https://player.vimeo.com/video/<?php echo esc_attr( $video_id ); ?>" 
                                title="<?php echo esc_attr( get_the_title() ); ?>"
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen
                                loading="lazy"
                            ></iframe>
                        </div>
                    <?php 
                        endif;
                        
                    // Generic video embed
                    else :
                    ?>
                        <div class="sgu-video-wrapper sgu-video-generic">
                            <video controls preload="metadata">
                                <source src="<?php echo esc_url( $media_url ); ?>">
                                <?php esc_html_e( 'Your browser does not support the video tag.', 'sgup' ); ?>
                            </video>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            <?php else : ?>
                
                <div class="sgu-apod-no-media">
                    <span class="sgu-no-media-icon" aria-hidden="true">🌌</span>
                    <p><?php esc_html_e( 'Media not available', 'sgup' ); ?></p>
                </div>
                
            <?php endif; ?>
            
        </div>
        
        <div class="sgu-apod-content">
            
            <h2 class="sgu-content-heading screen-reader-text"><?php esc_html_e( 'Explanation', 'sgup' ); ?></h2>
            
            <div class="sgu-apod-explanation">
                <?php the_content(); ?>
            </div>
            
        </div>
        
        <footer class="sgu-apod-footer">
            
            <div class="sgu-apod-share">
                <span class="sgu-share-label"><?php esc_html_e( 'Share:', 'sgup' ); ?></span>
                <a 
                    href="https://twitter.com/intent/tweet?url=<?php echo urlencode( get_permalink() ); ?>&text=<?php echo urlencode( get_the_title() ); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="sgu-share-link sgu-share-twitter"
                    aria-label="<?php esc_attr_e( 'Share on Twitter', 'sgup' ); ?>"
                >
                    𝕏
                </a>
                <a 
                    href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( get_permalink() ); ?>" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="sgu-share-link sgu-share-facebook"
                    aria-label="<?php esc_attr_e( 'Share on Facebook', 'sgup' ); ?>"
                >
                    f
                </a>
            </div>
            
            <div class="sgu-apod-meta">
                <p class="sgu-apod-source">
                    <?php esc_html_e( 'Source: NASA Astronomy Picture of the Day', 'sgup' ); ?>
                </p>
            </div>
            
        </footer>
        
    </article>
    
    <nav class="sgu-apod-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'sgup' ); ?>">
        
        <?php if ( $prev_post ) : ?>
            <a href="<?php echo esc_url( get_permalink( $prev_post ) ); ?>" class="sgu-nav-link sgu-nav-prev" rel="prev">
                <span class="sgu-nav-icon" aria-hidden="true">←</span>
                <span class="sgu-nav-label"><?php esc_html_e( 'Previous', 'sgup' ); ?></span>
                <span class="sgu-nav-title"><?php echo esc_html( wp_trim_words( $prev_post->post_title, 5 ) ); ?></span>
            </a>
        <?php else : ?>
            <span class="sgu-nav-placeholder"></span>
        <?php endif; ?>
        
        <?php if ( $next_post ) : ?>
            <a href="<?php echo esc_url( get_permalink( $next_post ) ); ?>" class="sgu-nav-link sgu-nav-next" rel="next">
                <span class="sgu-nav-label"><?php esc_html_e( 'Next', 'sgup' ); ?></span>
                <span class="sgu-nav-title"><?php echo esc_html( wp_trim_words( $next_post->post_title, 5 ) ); ?></span>
                <span class="sgu-nav-icon" aria-hidden="true">→</span>
            </a>
        <?php else : ?>
            <span class="sgu-nav-placeholder"></span>
        <?php endif; ?>
        
    </nav>
    
</main>

<?php 
endwhile;

get_footer(); 
?>
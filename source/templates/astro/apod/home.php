<?php
/**
 * Template: APOD Home
 * 
 * Displays the Astronomy Picture of the Day on the homepage
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var int $id Post ID
 * @var string $block_title The block/section title
 * @var string $title The APOD title
 * @var string $content The APOD explanation
 * @var array $meta Post meta data
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Extract meta values
$media_type = $meta['sgu_apod_local_media_type'][0] ?? 'image';
$local_media = $meta['sgu_apod_local_media'][0] ?? '';
$original_media = $meta['sgu_apod_orignal_media'][0] ?? '';
$copyright = $meta['sgu_apod_copyright'][0] ?? 'NASA/JPL';

// Use local media if available, fallback to original
$media_url = ! empty( $local_media ) ? $local_media : $original_media;

// Get the single post URL
$single_url = get_permalink( $id );

// Get the archive URL
$archive_url = SGU_Static::get_archive_url( 'sgup_photo_journals' );
?>

<section class="sgu-apod-home">
    
    <?php if ( ! empty( $block_title ) ) : ?>
        <header class="sgu-apod-header">
            <h2 class="sgu-apod-section-title"><?php echo esc_html( $block_title ); ?></h2>
            <?php if ( ! empty( $archive_url ) && $archive_url !== '/' ) : ?>
                <a href="<?php echo esc_url( $archive_url ); ?>" class="sgu-apod-archive-link">
                    <?php esc_html_e( 'View All', 'sgup' ); ?> →
                </a>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <article class="sgu-apod-card">
        
        <div class="sgu-apod-media">
            <?php if ( $media_type === 'video' ) : ?>
                <?php 
                // Check if it's a YouTube or Vimeo embed
                $is_youtube = strpos( $media_url, 'youtube.com' ) !== false || strpos( $media_url, 'youtu.be' ) !== false;
                $is_vimeo = strpos( $media_url, 'vimeo.com' ) !== false;
                
                if ( $is_youtube || $is_vimeo ) : ?>
                    <div class="sgu-apod-video-wrapper">
                        <iframe 
                            src="<?php echo esc_url( $media_url ); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            loading="lazy"
                            title="<?php echo esc_attr( $title ); ?>"
                        ></iframe>
                    </div>
                <?php else : ?>
                    <video controls preload="metadata" class="sgu-apod-video">
                        <source src="<?php echo esc_url( $media_url ); ?>" type="video/mp4">
                        <?php esc_html_e( 'Your browser does not support the video tag.', 'sgup' ); ?>
                    </video>
                <?php endif; ?>
            <?php else : ?>
                <a href="<?php echo esc_url( $single_url ); ?>" class="sgu-apod-image-link">
                    <img 
                        src="<?php echo esc_url( $media_url ); ?>" 
                        alt="<?php echo esc_attr( $title ); ?>"
                        class="sgu-apod-image"
                        loading="lazy"
                    />
                </a>
            <?php endif; ?>
        </div>
        
        <div class="sgu-apod-content">
            
            <h3 class="sgu-apod-title">
                <a href="<?php echo esc_url( $single_url ); ?>">
                    <?php echo esc_html( $title ); ?>
                </a>
            </h3>
            
            <div class="sgu-apod-meta">
                <time class="sgu-apod-date" datetime="<?php echo esc_attr( get_the_date( 'c', $id ) ); ?>">
                    <?php echo esc_html( get_the_date( 'F j, Y', $id ) ); ?>
                </time>
                <?php if ( ! empty( $copyright ) ) : ?>
                    <span class="sgu-apod-copyright">
                        <?php esc_html_e( 'Credit:', 'sgup' ); ?> <?php echo esc_html( $copyright ); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="sgu-apod-excerpt">
                <p><?php echo esc_html( wp_trim_words( $content, 50, '...' ) ); ?></p>
            </div>
            
            <a href="<?php echo esc_url( $single_url ); ?>" class="sgu-apod-readmore">
                <?php esc_html_e( 'Read More', 'sgup' ); ?> →
            </a>
            
        </div>
        
    </article>
    
</section>
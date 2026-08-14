<?php
/**
 * Template: APOD Archive
 * 
 * Displays archive listing of Astronomy Picture of the Day posts
 * 
 * @package US Star Gazers
 * @since 8.4
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

get_header();

$paged = SGU_Static::safe_get_paged_var();

$args = [
    'post_type' => 'sgu_apod',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
];

$apod_query = new WP_Query( $args );
?>

<main class="sgu-apod-archive">
    
    <header class="sgu-archive-header">
        <h1 class="sgu-archive-title"><?php esc_html_e( 'Astronomy Picture of the Day Archive', 'sgup' ); ?></h1>
        <p class="sgu-archive-description">
            <?php esc_html_e( 'Explore NASA\'s stunning collection of astronomical images, updated daily since 1995.', 'sgup' ); ?>
        </p>
    </header>
    
    <?php if ( $apod_query->have_posts() ) : ?>
        
        <div class="sgu-apod-grid">
            
            <?php while ( $apod_query->have_posts() ) : $apod_query->the_post(); ?>
                
                <?php
                $post_id = get_the_ID();
                $meta = get_post_meta( $post_id );
                $media_type = $meta['sgu_apod_local_media_type'][0] ?? 'image';
                $local_media = $meta['sgu_apod_local_media'][0] ?? '';
                $original_media = $meta['sgu_apod_orignal_media'][0] ?? '';
                $copyright = $meta['sgu_apod_copyright'][0] ?? 'NASA/JPL';
                $media_url = ! empty( $local_media ) ? $local_media : $original_media;
                ?>
                
                <article class="sgu-apod-card" id="post-<?php the_ID(); ?>">
                    
                    <a href="<?php the_permalink(); ?>" class="sgu-apod-card-link">
                        
                        <div class="sgu-apod-card-media">
                            
                            <?php if ( $media_type === 'image' && ! empty( $media_url ) ) : ?>
                                
                                <img 
                                    src="<?php echo esc_url( $media_url ); ?>" 
                                    alt="<?php echo esc_attr( get_the_title() ); ?>"
                                    class="sgu-apod-card-image"
                                    loading="lazy"
                                />
                                
                            <?php elseif ( $media_type === 'video' ) : ?>
                                
                                <div class="sgu-apod-card-video-placeholder">
                                    <span class="sgu-video-icon" aria-hidden="true">🎬</span>
                                    <span class="sgu-video-label"><?php esc_html_e( 'Video', 'sgup' ); ?></span>
                                </div>
                                
                            <?php else : ?>
                                
                                <div class="sgu-apod-card-placeholder">
                                    <span class="sgu-placeholder-icon" aria-hidden="true">🌌</span>
                                </div>
                                
                            <?php endif; ?>
                            
                            <div class="sgu-apod-card-overlay">
                                <span class="sgu-view-label"><?php esc_html_e( 'View Details', 'sgup' ); ?></span>
                            </div>
                            
                        </div>
                        
                        <div class="sgu-apod-card-content">
                            
                            <time class="sgu-apod-card-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
                            </time>
                            
                            <h2 class="sgu-apod-card-title"><?php the_title(); ?></h2>
                            
                            <p class="sgu-apod-card-excerpt">
                                <?php echo esc_html( wp_trim_words( get_the_content(), 20 ) ); ?>
                            </p>
                            
                            <?php if ( ! empty( $copyright ) ) : ?>
                                <span class="sgu-apod-card-credit">
                                    <?php esc_html_e( 'Credit:', 'sgup' ); ?> <?php echo esc_html( $copyright ); ?>
                                </span>
                            <?php endif; ?>
                            
                        </div>
                        
                    </a>
                    
                </article>
                
            <?php endwhile; ?>
            
        </div>
        
        <?php if ( $apod_query->max_num_pages > 1 ) : ?>
            <nav class="sgu-apod-pagination" aria-label="<?php esc_attr_e( 'APOD Archive Pagination', 'sgup' ); ?>">
                <?php echo SGU_Static::cpt_pagination( $apod_query->max_num_pages, $paged ); ?>
            </nav>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
    <?php else : ?>
        
        <div class="sgu-apod-none">
            <p><?php esc_html_e( 'No astronomy photos found. Check back soon!', 'sgup' ); ?></p>
        </div>
        
    <?php endif; ?>
    
</main>

<?php get_footer(); ?>
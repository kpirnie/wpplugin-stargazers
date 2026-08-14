<?php
/**
 * Template: Near Earth Objects
 * 
 * Displays Near Earth Objects (NEO) data with optional map and pagination
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var bool $show_paging Whether to show pagination
 * @var int $max_pages Maximum number of pages
 * @var int $per_page Items per page
 * @var string $paging_location Pagination location (top, bottom, both)
 * @var int $paged Current page number
 * @var bool $show_map Whether to show the map visualization
 * @var object $data WP_Query object containing posts
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Early return if no data
if ( ! $data || ! $data->posts ) {
    return;
}

// Pagination helper
$render_pagination = function() use ( $max_pages, $paged ) {
    echo SGU_Static::cpt_pagination( $max_pages, $paged );
};

// Helper to format large numbers
$format_distance = function( $km ) {
    if ( $km >= 1000000 ) {
        return number_format( $km / 1000000, 2 ) . ' million km';
    }
    return number_format( $km ) . ' km';
};

// Helper to format diameter
$format_diameter = function( $min, $max ) {
    $avg = ( $min + $max ) / 2;
    if ( $avg >= 1000 ) {
        return number_format( $avg / 1000, 2 ) . ' km';
    }
    return number_format( $avg ) . ' m';
};
?>

<div class="sgu-neos">
    
    <?php if ( $show_paging && in_array( $paging_location, [ 'top', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-top">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>

    <?php if ( $show_map ) : ?>
        <div class="sgu-neo-map-container">
            <div class="sgu-neo-map" id="sgu-neo-map">
                <p class="sgu-neo-map-placeholder"><?php esc_html_e( 'NEO Orbital Map Visualization', 'sgup' ); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="sgu-neo-list">
        
        <?php foreach ( $data->posts as $post ) : ?>
            
            <?php
            // Unserialize the content data
            $neo_data = maybe_unserialize( $post->post_content );
            
            // Extract key information
            $name = $post->post_title;
            $is_hazardous = get_post_meta( $post->ID, 'sgu_neo_hazardous', true );
            $is_hazardous = filter_var( $is_hazardous, FILTER_VALIDATE_BOOLEAN );
            
            // NASA JPL URL
            $nasa_jpl_url = $neo_data['nasa_jpl_url'] ?? '';
            
            // Diameter estimates (in meters)
            $diameter_min = $neo_data['estimated_diameter']['meters']['estimated_diameter_min'] ?? 0;
            $diameter_max = $neo_data['estimated_diameter']['meters']['estimated_diameter_max'] ?? 0;
            
            // Absolute magnitude
            $absolute_magnitude = $neo_data['absolute_magnitude_h'] ?? '';
            
            // Close approach data (get the first/closest approach)
            $close_approaches = $neo_data['close_approach_data'] ?? [];
            $closest_approach = ! empty( $close_approaches ) ? $close_approaches[0] : null;
            
            // Extract approach details
            $approach_date = $closest_approach['close_approach_date_full'] ?? '';
            $velocity_kph = $closest_approach['relative_velocity']['kilometers_per_hour'] ?? 0;
            $velocity_mph = $closest_approach['relative_velocity']['miles_per_hour'] ?? 0;
            $miss_distance_km = $closest_approach['miss_distance']['kilometers'] ?? 0;
            $miss_distance_lunar = $closest_approach['miss_distance']['lunar'] ?? 0;
            $orbiting_body = $closest_approach['orbiting_body'] ?? 'Earth';
            ?>
            
            <article class="sgu-neo-item <?php echo $is_hazardous ? 'sgu-neo-hazardous' : 'sgu-neo-safe'; ?>">
                
                <header class="sgu-neo-header">
                    <span class="sgu-neo-icon"><?php echo $is_hazardous ? '⚠️' : '🌍'; ?></span>
                    <div class="sgu-neo-title-wrap">
                        <h3 class="sgu-neo-title">
                            <?php echo esc_html( $name ); ?>
                            <?php if ( $is_hazardous ) : ?>
                                <span class="sgu-neo-hazard-badge"><?php esc_html_e( 'Potentially Hazardous', 'sgup' ); ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if ( ! empty( $approach_date ) ) : ?>
                            <time class="sgu-neo-date" datetime="<?php echo esc_attr( date( 'c', strtotime( $approach_date ) ) ); ?>">
                                <?php esc_html_e( 'Close Approach:', 'sgup' ); ?> 
                                <?php echo esc_html( date( 'F j, Y \a\t g:i A', strtotime( $approach_date ) ) ); ?> UTC
                            </time>
                        <?php endif; ?>
                    </div>
                </header>
                
                <div class="sgu-neo-body">
                    
                    <div class="sgu-neo-stats">
                        
                        <?php if ( $diameter_min > 0 || $diameter_max > 0 ) : ?>
                            <div class="sgu-neo-stat">
                                <span class="sgu-neo-stat-label"><?php esc_html_e( 'Est. Diameter', 'sgup' ); ?></span>
                                <span class="sgu-neo-stat-value"><?php echo esc_html( $format_diameter( $diameter_min, $diameter_max ) ); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $absolute_magnitude ) ) : ?>
                            <div class="sgu-neo-stat">
                                <span class="sgu-neo-stat-label"><?php esc_html_e( 'Magnitude (H)', 'sgup' ); ?></span>
                                <span class="sgu-neo-stat-value"><?php echo esc_html( number_format( $absolute_magnitude, 2 ) ); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $miss_distance_km > 0 ) : ?>
                            <div class="sgu-neo-stat">
                                <span class="sgu-neo-stat-label"><?php esc_html_e( 'Miss Distance', 'sgup' ); ?></span>
                                <span class="sgu-neo-stat-value">
                                    <?php echo esc_html( $format_distance( $miss_distance_km ) ); ?>
                                    <?php if ( $miss_distance_lunar > 0 ) : ?>
                                        <small>(<?php echo esc_html( number_format( $miss_distance_lunar, 2 ) ); ?> LD)</small>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $velocity_kph > 0 ) : ?>
                            <div class="sgu-neo-stat">
                                <span class="sgu-neo-stat-label"><?php esc_html_e( 'Relative Velocity', 'sgup' ); ?></span>
                                <span class="sgu-neo-stat-value">
                                    <?php echo esc_html( number_format( $velocity_kph ) ); ?> km/h
                                    <small>(<?php echo esc_html( number_format( $velocity_mph ) ); ?> mph)</small>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $orbiting_body ) ) : ?>
                            <div class="sgu-neo-stat">
                                <span class="sgu-neo-stat-label"><?php esc_html_e( 'Orbiting', 'sgup' ); ?></span>
                                <span class="sgu-neo-stat-value"><?php echo esc_html( $orbiting_body ); ?></span>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <?php if ( count( $close_approaches ) > 1 ) : ?>
                        <div class="sgu-neo-approaches">
                            <details class="sgu-neo-approach-details">
                                <summary><?php esc_html_e( 'All Close Approaches', 'sgup' ); ?> (<?php echo count( $close_approaches ); ?>)</summary>
                                <ul class="sgu-neo-approach-list">
                                    <?php foreach ( $close_approaches as $approach ) : ?>
                                        <li>
                                            <strong><?php echo esc_html( $approach['close_approach_date'] ?? '' ); ?></strong>
                                            - <?php echo esc_html( $format_distance( $approach['miss_distance']['kilometers'] ?? 0 ) ); ?>
                                            @ <?php echo esc_html( number_format( $approach['relative_velocity']['kilometers_per_hour'] ?? 0 ) ); ?> km/h
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ( ! empty( $nasa_jpl_url ) ) : ?>
                    <footer class="sgu-neo-footer">
                        <a href="<?php echo esc_url( $nasa_jpl_url ); ?>" target="_blank" rel="noopener noreferrer" class="sgu-neo-link">
                            <?php esc_html_e( 'View on NASA JPL', 'sgup' ); ?>
                            <span class="screen-reader-text"><?php esc_html_e( '(opens in new tab)', 'sgup' ); ?></span>
                            →
                        </a>
                    </footer>
                <?php endif; ?>
                
            </article>
            
        <?php endforeach; ?>
        
    </div>

    <?php if ( $show_paging && in_array( $paging_location, [ 'bottom', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-bottom">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>
    
</div>
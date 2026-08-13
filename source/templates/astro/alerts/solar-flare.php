<?php
/**
 * Template: Solar Flare Alerts
 * 
 * Displays Solar Flare alerts with pagination
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

// Flare class color mapping
$flare_colors = [
    'X' => 'red',
    'M' => 'orange',
    'C' => 'yellow',
    'B' => 'green',
    'A' => 'blue',
];
?>

<div class="sgu-flare-alerts">
    
    <?php if ( $show_paging && in_array( $paging_location, [ 'top', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-top">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>

    <div class="sgu-alerts-list">
        
        <?php foreach ( $data->posts as $post ) : ?>
            
            <?php
            // Unserialize the content data
            $flare_data = maybe_unserialize( $post->post_content );
            
            // Extract key information
            $flare_id = $post->post_title;
            $begin_time = isset( $flare_data['beginTime'] ) ? strtotime( $flare_data['beginTime'] ) : null;
            $peak_time = isset( $flare_data['peakTime'] ) ? strtotime( $flare_data['peakTime'] ) : null;
            $end_time = isset( $flare_data['endTime'] ) ? strtotime( $flare_data['endTime'] ) : null;
            $class_type = $flare_data['classType'] ?? '';
            $source_location = $flare_data['sourceLocation'] ?? '';
            $active_region = $flare_data['activeRegionNum'] ?? '';
            $instruments = $flare_data['instruments'] ?? [];
            $linked_events = $flare_data['linkedEvents'] ?? [];
            
            // Get flare class letter for color coding
            $flare_class = ! empty( $class_type ) ? strtoupper( substr( $class_type, 0, 1 ) ) : '';
            $flare_color = $flare_colors[ $flare_class ] ?? 'gray';
            ?>
            
            <article class="sgu-alert-item sgu-flare-item sgu-flare-<?php echo esc_attr( $flare_color ); ?>">
                
                <header class="sgu-alert-header">
                    <span class="sgu-alert-icon">🔥</span>
                    <div class="sgu-alert-title-wrap">
                        <h3 class="sgu-alert-title">
                            <?php if ( ! empty( $class_type ) ) : ?>
                                <span class="sgu-flare-class sgu-flare-class-<?php echo esc_attr( $flare_color ); ?>">
                                    <?php echo esc_html( $class_type ); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo esc_html( $flare_id ); ?>
                        </h3>
                        <?php if ( $begin_time ) : ?>
                            <time class="sgu-alert-date" datetime="<?php echo esc_attr( date( 'c', $begin_time ) ); ?>">
                                <?php echo esc_html( date( 'F j, Y \a\t g:i A', $begin_time ) ); ?> UTC
                            </time>
                        <?php endif; ?>
                    </div>
                </header>
                
                <div class="sgu-alert-body">
                    
                    <div class="sgu-flare-timeline">
                        <?php if ( $begin_time ) : ?>
                            <div class="sgu-alert-detail">
                                <strong><?php esc_html_e( 'Begin:', 'sgup' ); ?></strong>
                                <span><?php echo esc_html( date( 'g:i A', $begin_time ) ); ?> UTC</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $peak_time ) : ?>
                            <div class="sgu-alert-detail">
                                <strong><?php esc_html_e( 'Peak:', 'sgup' ); ?></strong>
                                <span><?php echo esc_html( date( 'g:i A', $peak_time ) ); ?> UTC</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $end_time ) : ?>
                            <div class="sgu-alert-detail">
                                <strong><?php esc_html_e( 'End:', 'sgup' ); ?></strong>
                                <span><?php echo esc_html( date( 'g:i A', $end_time ) ); ?> UTC</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ( ! empty( $source_location ) ) : ?>
                        <div class="sgu-alert-detail">
                            <strong><?php esc_html_e( 'Source Location:', 'sgup' ); ?></strong>
                            <span><?php echo esc_html( $source_location ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $active_region ) ) : ?>
                        <div class="sgu-alert-detail">
                            <strong><?php esc_html_e( 'Active Region:', 'sgup' ); ?></strong>
                            <span>AR <?php echo esc_html( $active_region ); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $instruments ) ) : ?>
                        <div class="sgu-alert-detail">
                            <strong><?php esc_html_e( 'Instruments:', 'sgup' ); ?></strong>
                            <span>
                                <?php 
                                $instrument_names = array_map( function( $inst ) {
                                    return $inst['displayName'] ?? '';
                                }, $instruments );
                                echo esc_html( implode( ', ', array_filter( $instrument_names ) ) );
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $linked_events ) ) : ?>
                        <div class="sgu-alert-linked">
                            <strong><?php esc_html_e( 'Linked Events:', 'sgup' ); ?></strong>
                            <ul>
                                <?php foreach ( $linked_events as $event ) : ?>
                                    <li><?php echo esc_html( $event['activityID'] ?? '' ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ( ! empty( $class_type ) ) : ?>
                    <footer class="sgu-alert-footer">
                        <div class="sgu-flare-intensity">
                            <span class="sgu-flare-badge sgu-flare-badge-<?php echo esc_attr( $flare_color ); ?>">
                                <?php 
                                $intensity_labels = [
                                    'X' => __( 'Extreme', 'sgup' ),
                                    'M' => __( 'Strong', 'sgup' ),
                                    'C' => __( 'Moderate', 'sgup' ),
                                    'B' => __( 'Minor', 'sgup' ),
                                    'A' => __( 'Minimal', 'sgup' ),
                                ];
                                echo esc_html( $intensity_labels[ $flare_class ] ?? __( 'Unknown', 'sgup' ) );
                                ?>
                            </span>
                        </div>
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
<?php
/**
 * Template: CME Alerts
 * 
 * Displays Coronal Mass Ejection alerts with pagination
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
?>

<div class="sgu-cme-alerts">
    
    <?php if ( $show_paging && in_array( $paging_location, [ 'top', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-top">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>

    <div class="sgu-alerts-list">
        
        <?php foreach ( $data->posts as $post ) : ?>
            
            <?php
            // Unserialize the content data
            $alert_data = maybe_unserialize( $post->post_content );
            
            // Extract key information
            $activity_id = $post->post_title;
            $start_time = isset( $alert_data['startTime'] ) ? strtotime( $alert_data['startTime'] ) : strtotime( $post->post_date );
            $note = $alert_data['note'] ?? '';
            $source_location = $alert_data['sourceLocation'] ?? '';
            $instruments = $alert_data['instruments'] ?? [];
            $cme_analyses = $alert_data['cmeAnalyses'] ?? [];
            $linked_events = $alert_data['linkedEvents'] ?? [];
            ?>
            
            <article class="sgu-alert-item sgu-cme-item">
                
                <header class="sgu-alert-header">
                    <span class="sgu-alert-icon">☀️</span>
                    <div class="sgu-alert-title-wrap">
                        <h3 class="sgu-alert-title"><?php echo esc_html( $activity_id ); ?></h3>
                        <time class="sgu-alert-date" datetime="<?php echo esc_attr( date( 'c', $start_time ) ); ?>">
                            <?php echo esc_html( date( 'F j, Y \a\t g:i A', $start_time ) ); ?> UTC
                        </time>
                    </div>
                </header>
                
                <div class="sgu-alert-body">
                    
                    <?php if ( ! empty( $source_location ) ) : ?>
                        <div class="sgu-alert-detail">
                            <strong><?php esc_html_e( 'Source Location:', 'sgup' ); ?></strong>
                            <span><?php echo esc_html( $source_location ); ?></span>
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
                    
                    <?php if ( ! empty( $cme_analyses ) ) : ?>
                        <?php $analysis = $cme_analyses[0]; ?>
                        <div class="sgu-cme-analysis">
                            <h4><?php esc_html_e( 'CME Analysis', 'sgup' ); ?></h4>
                            
                            <?php if ( isset( $analysis['speed'] ) ) : ?>
                                <div class="sgu-alert-detail">
                                    <strong><?php esc_html_e( 'Speed:', 'sgup' ); ?></strong>
                                    <span><?php echo esc_html( number_format( $analysis['speed'] ) ); ?> km/s</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( isset( $analysis['type'] ) ) : ?>
                                <div class="sgu-alert-detail">
                                    <strong><?php esc_html_e( 'Type:', 'sgup' ); ?></strong>
                                    <span><?php echo esc_html( $analysis['type'] ); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( isset( $analysis['latitude'] ) && isset( $analysis['longitude'] ) ) : ?>
                                <div class="sgu-alert-detail">
                                    <strong><?php esc_html_e( 'Direction:', 'sgup' ); ?></strong>
                                    <span><?php echo esc_html( $analysis['latitude'] ); ?>° / <?php echo esc_html( $analysis['longitude'] ); ?>°</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( isset( $analysis['halfAngle'] ) ) : ?>
                                <div class="sgu-alert-detail">
                                    <strong><?php esc_html_e( 'Half Angle:', 'sgup' ); ?></strong>
                                    <span><?php echo esc_html( $analysis['halfAngle'] ); ?>°</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $analysis['note'] ) ) : ?>
                                <div class="sgu-alert-note">
                                    <strong><?php esc_html_e( 'Analysis Note:', 'sgup' ); ?></strong>
                                    <p><?php echo esc_html( $analysis['note'] ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $note ) ) : ?>
                        <div class="sgu-alert-note">
                            <strong><?php esc_html_e( 'Note:', 'sgup' ); ?></strong>
                            <p><?php echo esc_html( $note ); ?></p>
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
                
            </article>
            
        <?php endforeach; ?>
        
    </div>

    <?php if ( $show_paging && in_array( $paging_location, [ 'bottom', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-bottom">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>
    
</div>
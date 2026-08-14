<?php
/**
 * Template: Geomagnetic Alerts
 * 
 * Displays Geomagnetic Storm alerts with pagination
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

<div class="sgu-geo-alerts">
    
    <?php if ( $show_paging && in_array( $paging_location, [ 'top', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-top">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>

    <div class="sgu-alerts-list">
        
        <?php foreach ( $data->posts as $post ) : ?>
            
            <?php
            // Unserialize the content data - geomagnetic data is typically plain text
            $geo_data = maybe_unserialize( $post->post_content );
            
            // The content is usually raw text from NOAA
            $raw_content = is_array( $geo_data ) ? implode( "\n", $geo_data ) : $geo_data;
            
            // Parse key information from the title
            $title = $post->post_title;
            $post_date = strtotime( $post->post_date );
            
            // Try to extract Kp index values from the content
            $kp_values = [];
            if ( preg_match_all( '/Kp\s*[=:]\s*(\d+)/', $raw_content, $matches ) ) {
                $kp_values = $matches[1];
            }
            
            // Try to extract G-scale storm level
            $g_scale = '';
            if ( preg_match( '/G([1-5])/', $raw_content, $matches ) ) {
                $g_scale = 'G' . $matches[1];
            }
            
            // G-scale severity mapping
            $g_scale_info = [
                'G1' => [ 'label' => __( 'Minor', 'sgup' ), 'color' => 'green' ],
                'G2' => [ 'label' => __( 'Moderate', 'sgup' ), 'color' => 'yellow' ],
                'G3' => [ 'label' => __( 'Strong', 'sgup' ), 'color' => 'orange' ],
                'G4' => [ 'label' => __( 'Severe', 'sgup' ), 'color' => 'red' ],
                'G5' => [ 'label' => __( 'Extreme', 'sgup' ), 'color' => 'purple' ],
            ];
            
            $storm_info = $g_scale_info[ $g_scale ] ?? [ 'label' => __( 'Unknown', 'sgup' ), 'color' => 'gray' ];
            ?>
            
            <article class="sgu-alert-item sgu-geo-item <?php echo ! empty( $g_scale ) ? 'sgu-geo-' . esc_attr( $storm_info['color'] ) : ''; ?>">
                
                <header class="sgu-alert-header">
                    <span class="sgu-alert-icon">🧲</span>
                    <div class="sgu-alert-title-wrap">
                        <h3 class="sgu-alert-title">
                            <?php if ( ! empty( $g_scale ) ) : ?>
                                <span class="sgu-geo-scale sgu-geo-scale-<?php echo esc_attr( $storm_info['color'] ); ?>">
                                    <?php echo esc_html( $g_scale ); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo esc_html( $title ); ?>
                        </h3>
                        <time class="sgu-alert-date" datetime="<?php echo esc_attr( date( 'c', $post_date ) ); ?>">
                            <?php echo esc_html( date( 'F j, Y \a\t g:i A', $post_date ) ); ?> UTC
                        </time>
                    </div>
                </header>
                
                <div class="sgu-alert-body">
                    
                    <?php if ( ! empty( $g_scale ) ) : ?>
                        <div class="sgu-geo-storm-level">
                            <div class="sgu-alert-detail">
                                <strong><?php esc_html_e( 'Storm Level:', 'sgup' ); ?></strong>
                                <span class="sgu-geo-badge sgu-geo-badge-<?php echo esc_attr( $storm_info['color'] ); ?>">
                                    <?php echo esc_html( $g_scale . ' - ' . $storm_info['label'] ); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $kp_values ) ) : ?>
                        <div class="sgu-geo-kp-index">
                            <strong><?php esc_html_e( 'Kp Index Values:', 'sgup' ); ?></strong>
                            <div class="sgu-kp-values">
                                <?php foreach ( $kp_values as $kp ) : ?>
                                    <?php 
                                    $kp_int = (int) $kp;
                                    $kp_color = 'green';
                                    if ( $kp_int >= 7 ) $kp_color = 'red';
                                    elseif ( $kp_int >= 5 ) $kp_color = 'orange';
                                    elseif ( $kp_int >= 4 ) $kp_color = 'yellow';
                                    ?>
                                    <span class="sgu-kp-value sgu-kp-<?php echo esc_attr( $kp_color ); ?>">
                                        <?php echo esc_html( $kp ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $raw_content ) ) : ?>
                        <div class="sgu-geo-forecast">
                            <details class="sgu-geo-details">
                                <summary><?php esc_html_e( 'View Full Forecast', 'sgup' ); ?></summary>
                                <pre class="sgu-geo-content"><?php echo esc_html( $raw_content ); ?></pre>
                            </details>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ( ! empty( $g_scale ) ) : ?>
                    <footer class="sgu-alert-footer">
                        <div class="sgu-geo-impacts">
                            <strong><?php esc_html_e( 'Potential Impacts:', 'sgup' ); ?></strong>
                            <?php 
                            $impacts = [
                                'G1' => __( 'Weak power grid fluctuations, minor satellite operations impact, aurora visible at high latitudes.', 'sgup' ),
                                'G2' => __( 'Power systems may experience voltage alarms, satellite orientation corrections needed, aurora visible at higher latitudes.', 'sgup' ),
                                'G3' => __( 'Voltage corrections required, surface charging on satellites, radio propagation affected, aurora visible at mid-latitudes.', 'sgup' ),
                                'G4' => __( 'Widespread voltage control problems, spacecraft charging issues, HF radio propagation sporadic, aurora visible at lower latitudes.', 'sgup' ),
                                'G5' => __( 'Grid systems may collapse, extensive spacecraft charging, HF radio blackout for days, aurora visible at tropical latitudes.', 'sgup' ),
                            ];
                            ?>
                            <p class="sgu-impact-text"><?php echo esc_html( $impacts[ $g_scale ] ?? '' ); ?></p>
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
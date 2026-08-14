<?php
/**
 * Template: Latest Alerts
 * 
 * Displays the most recent alerts from each alert type
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var object $latest_alerts Object containing latest posts by CPT type
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Early return if no alerts
if ( ! $latest_alerts ) {
    return;
}

// Define alert type configurations
$alert_types = [
    'sgu_cme_alerts' => [
        'label' => __( 'CME Alert', 'sgup' ),
        'icon' => '☀️',
        'color' => 'orange',
    ],
    'sgu_sw_alerts' => [
        'label' => __( 'Space Weather', 'sgup' ),
        'icon' => '🌌',
        'color' => 'purple',
    ],
    'sgu_geo_alerts' => [
        'label' => __( 'Geomagnetic', 'sgup' ),
        'icon' => '🧲',
        'color' => 'blue',
    ],
    'sgu_sf_alerts' => [
        'label' => __( 'Solar Flare', 'sgup' ),
        'icon' => '🔥',
        'color' => 'red',
    ],
];
?>

<div class="sgu-latest-alerts">
    
    <?php if ( ! empty( $title ) ) : ?>
        <h2 class="sgu-alerts-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <div class="sgu-alerts-grid">
        
        <?php foreach ( $alert_types as $cpt => $config ) : ?>
            
            <?php 
            // Get the alert for this type
            $alert = $latest_alerts->{$cpt} ?? false;
            
            // Skip if no alert exists
            if ( ! $alert || empty( $alert ) ) {
                continue;
            }
            
            // Get the first post
            $post = is_array( $alert ) ? $alert[0] : $alert;
            ?>
            
            <div class="sgu-alert-card sgu-alert-<?php echo esc_attr( $config['color'] ); ?>">
                
                <div class="sgu-alert-header">
                    <span class="sgu-alert-icon"><?php echo esc_html( $config['icon'] ); ?></span>
                    <span class="sgu-alert-type"><?php echo esc_html( $config['label'] ); ?></span>
                </div>
                
                <div class="sgu-alert-content">
                    <h3 class="sgu-alert-title"><?php echo esc_html( $post->post_title ); ?></h3>
                    
                    <div class="sgu-alert-meta">
                        <time class="sgu-alert-date" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>">
                            <?php echo esc_html( get_the_date( 'M j, Y g:i A', $post ) ); ?>
                        </time>
                    </div>
                    
                    <?php 
                    // Get content - may be serialized data
                    $content = $post->post_content;
                    $data = maybe_unserialize( $content );
                    
                    if ( is_array( $data ) ) :
                        // CME Alert
                        if ( isset( $data['note'] ) ) : ?>
                            <p class="sgu-alert-excerpt"><?php echo esc_html( wp_trim_words( $data['note'], 25 ) ); ?></p>
                        <?php 
                        // Solar Flare
                        elseif ( isset( $data['classType'] ) ) : ?>
                            <p class="sgu-alert-excerpt">
                                <strong><?php esc_html_e( 'Class:', 'sgup' ); ?></strong> <?php echo esc_html( $data['classType'] ); ?>
                                <?php if ( isset( $data['sourceLocation'] ) ) : ?>
                                    <br><strong><?php esc_html_e( 'Location:', 'sgup' ); ?></strong> <?php echo esc_html( $data['sourceLocation'] ); ?>
                                <?php endif; ?>
                            </p>
                        <?php 
                        // Space Weather
                        elseif ( isset( $data['message'] ) ) : ?>
                            <p class="sgu-alert-excerpt"><?php echo esc_html( wp_trim_words( $data['message'], 25 ) ); ?></p>
                        <?php endif;
                    
                    elseif ( is_string( $data ) ) : ?>
                        <p class="sgu-alert-excerpt"><?php echo esc_html( wp_trim_words( strip_tags( $data ), 25 ) ); ?></p>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        <?php endforeach; ?>
        
    </div>
    
</div>
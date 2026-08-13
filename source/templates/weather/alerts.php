<?php
/**
 * Template: Weather Alerts
 * 
 * Displays NOAA weather alerts for the user's location
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var int $max_alerts Maximum number of alerts to display
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var array $alerts Array of alert objects
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

/**
 * Get severity class for styling
 */
function sgu_get_alert_severity_class( string $severity ) : string {
    return match( strtolower( $severity ) ) {
        'extreme' => 'sgu-alert-extreme',
        'severe' => 'sgu-alert-severe',
        'moderate' => 'sgu-alert-moderate',
        'minor' => 'sgu-alert-minor',
        default => 'sgu-alert-unknown',
    };
}

/**
 * Get severity icon
 */
function sgu_get_alert_severity_icon( string $severity ) : string {
    return match( strtolower( $severity ) ) {
        'extreme' => '🚨',
        'severe' => '⚠️',
        'moderate' => '⚡',
        'minor' => 'ℹ️',
        default => '📢',
    };
}
?>

<div class="sgu-weather-container sgu-weather-alerts">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view weather alerts.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( empty( $alerts ) ) : ?>
        
        <div class="sgu-alerts-none">
            <div class="sgu-alerts-clear">
                <span class="sgu-alerts-clear-icon" aria-hidden="true">✅</span>
                <p><?php esc_html_e( 'No active weather alerts for your area.', 'sgup' ); ?></p>
            </div>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-alerts-content">
            
            <div class="sgu-alerts-count">
                <span class="sgu-alerts-badge"><?php echo esc_html( count( $alerts ) ); ?></span>
                <span class="sgu-alerts-label">
                    <?php echo esc_html( sprintf( 
                        _n( 'Active Alert', 'Active Alerts', count( $alerts ), 'sgup' ), 
                        count( $alerts ) 
                    ) ); ?>
                </span>
            </div>
            
            <div class="sgu-alerts-list">
                
                <?php foreach ( $alerts as $alert ) : ?>
                    
                    <?php
                    $severity = $alert->severity ?? 'unknown';
                    $severity_class = sgu_get_alert_severity_class( $severity );
                    $severity_icon = sgu_get_alert_severity_icon( $severity );
                    $event = $alert->event ?? __( 'Weather Alert', 'sgup' );
                    $headline = $alert->headline ?? '';
                    $description = $alert->description ?? '';
                    $instruction = $alert->instruction ?? '';
                    $effective = $alert->effective ?? '';
                    $expires = $alert->expires ?? '';
                    $urgency = $alert->urgency ?? '';
                    $sender = $alert->senderName ?? '';
                    ?>
                    
                    <article class="sgu-alert-item <?php echo esc_attr( $severity_class ); ?>">
                        
                        <header class="sgu-alert-header">
                            
                            <div class="sgu-alert-title-wrap">
                                <span class="sgu-alert-icon" aria-hidden="true"><?php echo esc_html( $severity_icon ); ?></span>
                                <h3 class="sgu-alert-event"><?php echo esc_html( $event ); ?></h3>
                            </div>
                            
                            <div class="sgu-alert-meta">
                                
                                <?php if ( $severity ) : ?>
                                    <span class="sgu-alert-severity sgu-severity-<?php echo esc_attr( strtolower( $severity ) ); ?>">
                                        <?php echo esc_html( ucfirst( $severity ) ); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ( $urgency ) : ?>
                                    <span class="sgu-alert-urgency">
                                        <?php echo esc_html( ucfirst( $urgency ) ); ?>
                                    </span>
                                <?php endif; ?>
                                
                            </div>
                            
                        </header>
                        
                        <?php if ( $headline ) : ?>
                            <div class="sgu-alert-headline">
                                <p><?php echo esc_html( $headline ); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="sgu-alert-times">
                            
                            <?php if ( $effective ) : ?>
                                <div class="sgu-alert-time sgu-alert-effective">
                                    <span class="sgu-time-label"><?php esc_html_e( 'Effective:', 'sgup' ); ?></span>
                                    <time datetime="<?php echo esc_attr( $effective ); ?>">
                                        <?php echo esc_html( date( 'M j, Y g:i A', strtotime( $effective ) ) ); ?>
                                    </time>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( $expires ) : ?>
                                <div class="sgu-alert-time sgu-alert-expires">
                                    <span class="sgu-time-label"><?php esc_html_e( 'Expires:', 'sgup' ); ?></span>
                                    <time datetime="<?php echo esc_attr( $expires ); ?>">
                                        <?php echo esc_html( date( 'M j, Y g:i A', strtotime( $expires ) ) ); ?>
                                    </time>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <?php if ( $description ) : ?>
                            <details class="sgu-alert-details">
                                <summary><?php esc_html_e( 'View Full Description', 'sgup' ); ?></summary>
                                <div class="sgu-alert-description">
                                    <?php echo nl2br( esc_html( $description ) ); ?>
                                </div>
                            </details>
                        <?php endif; ?>
                        
                        <?php if ( $instruction ) : ?>
                            <div class="sgu-alert-instruction">
                                <h4><?php esc_html_e( 'Recommended Actions', 'sgup' ); ?></h4>
                                <p><?php echo nl2br( esc_html( $instruction ) ); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( $sender ) : ?>
                            <footer class="sgu-alert-footer">
                                <small class="sgu-alert-sender">
                                    <?php esc_html_e( 'Issued by:', 'sgup' ); ?> <?php echo esc_html( $sender ); ?>
                                </small>
                            </footer>
                        <?php endif; ?>
                        
                    </article>
                    
                <?php endforeach; ?>
                
            </div>
            
        </div>
        
    <?php endif; ?>
    
</div>
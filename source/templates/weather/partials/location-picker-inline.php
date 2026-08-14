<?php
/**
 * Partial: Location Picker Inline
 * 
 * Inline location display with edit capability
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables (from parent template):
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

$display_name = '';
if ( $has_location && $location ) {
    if ( ! empty( $location_name ) ) {
        $parts = [];
        if ( ! empty( $location_name->city ) ) $parts[] = $location_name->city;
        if ( ! empty( $location_name->state ) ) $parts[] = $location_name->state;
        $display_name = implode( ', ', $parts );
    } elseif ( isset( $location->lat ) && isset( $location->lon ) ) {
        $display_name = sprintf( '%s, %s', round( $location->lat, 4 ), round( $location->lon, 4 ) );
    }
}
?>

<div class="sgu-location-picker-inline" data-has-location="<?php echo $has_location ? 'true' : 'false'; ?>">
    
    <?php if ( $has_location && ! empty( $display_name ) ) : ?>
        
        <div class="sgu-location-current">
            <span class="sgu-location-icon" aria-hidden="true">📍</span>
            <span class="sgu-location-display"><?php echo esc_html( $display_name ); ?></span>
            <button 
                type="button" 
                class="sgu-location-change-btn"
                aria-label="<?php esc_attr_e( 'Change location', 'sgup' ); ?>"
                data-action="toggle-location-form"
            >
                <span class="sgu-btn-text"><?php esc_html_e( 'Change', 'sgup' ); ?></span>
                <span class="sgu-btn-icon" aria-hidden="true">✏️</span>
            </button>
        </div>
        
        <div class="sgu-location-form-wrapper" aria-hidden="true" style="display: none;">
            <?php include __DIR__ . '/location-form.php'; ?>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-location-prompt-inline">
            <span class="sgu-location-icon" aria-hidden="true">📍</span>
            <span class="sgu-location-prompt-text"><?php esc_html_e( 'Set your location for local weather', 'sgup' ); ?></span>
            <button 
                type="button" 
                class="sgu-location-set-btn"
                aria-label="<?php esc_attr_e( 'Set location', 'sgup' ); ?>"
                data-action="toggle-location-form"
            >
                <?php esc_html_e( 'Set Location', 'sgup' ); ?>
            </button>
        </div>
        
        <div class="sgu-location-form-wrapper" aria-hidden="true" style="display: none;">
            <?php include __DIR__ . '/location-form.php'; ?>
        </div>
        
    <?php endif; ?>
    
</div>
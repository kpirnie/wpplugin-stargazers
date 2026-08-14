<?php
/**
 * Template: Light Pollution Map
 * 
 * Displays an interactive light pollution map using Leaflet with VIIRS overlay
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var int $max_height Number of pixels for the map height
 * @var string $wrapper_attr Extra wrapper attributes
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var string $location_name Location name
 * @var float $latitude User's latitude
 * @var float $longitude User's longitude
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Generate unique ID for this instance
$map_id = 'sgu-lp-map-' . wp_unique_id();

// Build location label for popup
$popup_content = '';
if ( $has_location && ! empty( $location_name ) ) {
    $popup_content = '<small>' . esc_js( $location_name );
    if ( ! empty( $location->state ) ) {
        $popup_content .= ' ' . esc_js( $location->state );
    }
    $popup_content .= '</small>';
} else {
    $popup_content = '<small>' . esc_js( round( $latitude, 4 ) ) . ', ' . esc_js( round( $longitude, 4 ) ) . '</small>';
}

// Enqueue required assets
wp_enqueue_style( 'leaflet' );
wp_enqueue_script( 'sgu-light-pollution-map' );
wp_enqueue_style( 'sgu-light-pollution-map' );

// Pass config to JS
wp_add_inline_script( 'sgu-light-pollution-map', 'window.sguLightPollutionMaps = window.sguLightPollutionMaps || [];
window.sguLightPollutionMaps.push(' . wp_json_encode( [
    'mapId'        => $map_id,
    'lat'          => $latitude,
    'lng'          => $longitude,
    'popupContent' => $popup_content,
] ) . ');', 'before' );
?>

<div class="sgu-light-pollution-container" <?php echo $wrapper_attr; ?>>

    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-light-pollution-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php 
        $theme_template = locate_template( [
            'templates/weather/partials/location-picker-inline.php',
            'sgu/weather/partials/location-picker-inline.php',
        ] );
        $partial = $theme_template ?: SGUP_PATH . '/templates/weather/partials/location-picker-inline.php';
        if ( file_exists( $partial ) ) {
            include $partial;
        }
        ?>
    <?php endif; ?>

    <div 
        id="<?php echo esc_attr( $map_id ); ?>" 
        class="sgu-light-pollution-map"
        style="height:<?php echo esc_attr( $max_height ); ?>px;"
    ></div>

    <div class="sgu-lp-legend">
        <span><span class="sgu-lp-legend-color" style="background:#000000;"></span> Dark</span>
        <span><span class="sgu-lp-legend-color" style="background:#0b0b2a;"></span> Min.</span>
        <span><span class="sgu-lp-legend-color" style="background:#1a1a4a;"></span> Low</span>
        <span><span class="sgu-lp-legend-color" style="background:#3d3d7a;"></span> Moderate</span>
        <span><span class="sgu-lp-legend-color" style="background:#7a7a00;"></span> Bright</span>
        <span><span class="sgu-lp-legend-color" style="background:#ffaa00;"></span> Brighter</span>
        <span><span class="sgu-lp-legend-color" style="background:#ffffff;"></span> Intense</span>
    </div>

</div>
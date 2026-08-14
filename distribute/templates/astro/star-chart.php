<?php
/**
 * Star Chart Template - iframe version
 * 
 * Uses VirtualSky iframe embed from Las Cumbres Observatory
 * https://virtualsky.lco.global/embed/
 * 
 * @package US_Stargazers_Plugin
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Build VirtualSky iframe URL parameters
$iframe_params = [
    'projection' => $projection ?? 'stereo',
    'latitude' => $latitude,
    'longitude' => $longitude,
    'az' => absint( $az ?? 180 ),
    'gradient' => filter_var( $gradient ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'constellations' => filter_var( $show_constellations ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'constellationlabels' => filter_var( $show_constellation_labels ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showplanets' => filter_var( $show_planets ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showplanetlabels' => filter_var( $show_planet_labels ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showstars' => filter_var( $show_stars ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showstarlabels' => filter_var( $show_star_labels ?? false, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showorbits' => filter_var( $show_orbits ?? false, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showgalaxy' => filter_var( $show_galaxy ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showground' => filter_var( $ground ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'showdate' => 'true',
    'showposition' => 'true',
    'mouse' => filter_var( $mouse ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
    'keyboard' => filter_var( $keyboard ?? true, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
];

// Apply style-specific settings
switch($style ?? 'default') {
    case 'inverted':
        $iframe_params['negative'] = 'true';
        break;
    case 'navy':
        $iframe_params['color'] = '#000033';
        break;
    case 'red':
        $iframe_params['color'] = '#330000';
        break;
}

// Build iframe URL
$base_url = 'https://virtualsky.lco.global/embed/index.html';
$iframe_url = add_query_arg( $iframe_params, $base_url );

// Calculate dynamic height based on zoom level
$height = 400 + ($zoom * 50);
?>

<div class="sgu-star-chart-container" <?php echo $wrapper_attr; ?>>

    <?php // Display optional title ?>
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-star-chart-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php // Display optional location picker ?>
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

    <div class="sgu-star-chart-card">
        <?php 
        /**
         * VirtualSky iframe embed
         * 
         * Uses Las Cumbres Observatory's hosted VirtualSky instance
         */
        ?>
        <iframe 
            src="<?php echo esc_url( $iframe_url ); ?>" 
            style="width: 100%; height: <?php echo esc_attr($height); ?>px; border: none;"
            title="<?php echo esc_attr( $title ); ?>"
            loading="lazy"
        ></iframe>
        
        <?php // Display location info and usage instructions ?>
        <p class="sgu-star-chart-info" style="margin-top: 0.5rem; font-size: 0.875rem; color: #888;">
            Location: <?php echo esc_html($location_name); ?> 
            (<?php echo number_format($latitude, 2); ?>, <?php echo number_format($longitude, 2); ?>)
            <br>
            <em>Click and drag to explore • Use mouse wheel to zoom • Arrow keys to navigate</em>
        </p>
    </div>

</div>
<?php
/**
 * Template: Sun Rise/Set
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * @var string $title
 * @var bool $show_title
 * @var bool $show_location_picker
 * @var string $wrapper_attr
 * @var bool $has_location
 * @var object|null $location
 * @var string $location_name
 * @var float $latitude
 * @var float $longitude
 * @var object|null $sun
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-sun-riseset-container" <?php echo $wrapper_attr; ?>>

    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-sun-riseset-title"><?php echo esc_html( $title ); ?></h2>
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

    <?php if ( $sun ) : ?>
        <div class="sgu-sun-riseset-card">
            <div class="sgu-sun-riseset-times">
                <?php if ( $sun->civil_twilight_begin ) : ?>
                    <div class="sgu-sun-riseset-row">
                        <span class="sgu-sun-riseset-label">Dawn</span>
                        <span class="sgu-sun-riseset-value"><?php echo esc_html( $sun->civil_twilight_begin ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $sun->rise ) : ?>
                    <div class="sgu-sun-riseset-row">
                        <span class="sgu-sun-riseset-label">Sunrise</span>
                        <span class="sgu-sun-riseset-value"><?php echo esc_html( $sun->rise ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $sun->transit ) : ?>
                    <div class="sgu-sun-riseset-row">
                        <span class="sgu-sun-riseset-label">Solar Noon</span>
                        <span class="sgu-sun-riseset-value"><?php echo esc_html( $sun->transit ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $sun->set ) : ?>
                    <div class="sgu-sun-riseset-row">
                        <span class="sgu-sun-riseset-label">Sunset</span>
                        <span class="sgu-sun-riseset-value"><?php echo esc_html( $sun->set ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $sun->civil_twilight_end ) : ?>
                    <div class="sgu-sun-riseset-row">
                        <span class="sgu-sun-riseset-label">Dusk</span>
                        <span class="sgu-sun-riseset-value"><?php echo esc_html( $sun->civil_twilight_end ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <p class="sgu-sun-riseset-error">Unable to retrieve sun data.</p>
    <?php endif; ?>

</div>
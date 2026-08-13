<?php
/**
 * Template: Moon Rise/Set
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
 * @var object|null $moon
 * @var object|null $moon_phase
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-moon-riseset-container" <?php echo $wrapper_attr; ?>>

    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-moon-riseset-title"><?php echo esc_html( $title ); ?></h2>
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

    <?php if ( $moon ) : ?>
        <div class="sgu-moon-riseset-card">
            <?php if ( $moon_phase ) : ?>
                <div class="sgu-moon-riseset-phase">
                    <span class="sgu-moon-riseset-phase-name"><?php echo esc_html( $moon_phase->current ); ?></span>
                    <span class="sgu-moon-riseset-phase-illum"><?php echo esc_html( $moon_phase->illumination ); ?> illuminated</span>
                </div>
            <?php endif; ?>

            <div class="sgu-moon-riseset-times">
                <?php if ( $moon->rise ) : ?>
                    <div class="sgu-moon-riseset-row">
                        <span class="sgu-moon-riseset-label">Moonrise</span>
                        <span class="sgu-moon-riseset-value"><?php echo esc_html( $moon->rise ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $moon->transit ) : ?>
                    <div class="sgu-moon-riseset-row">
                        <span class="sgu-moon-riseset-label">Transit</span>
                        <span class="sgu-moon-riseset-value"><?php echo esc_html( $moon->transit ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $moon->set ) : ?>
                    <div class="sgu-moon-riseset-row">
                        <span class="sgu-moon-riseset-label">Moonset</span>
                        <span class="sgu-moon-riseset-value"><?php echo esc_html( $moon->set ); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( $moon_phase && $moon_phase->closest ) : ?>
                <div class="sgu-moon-riseset-next">
                    Next: <?php echo esc_html( $moon_phase->closest->phase ); ?> 
                    on <?php echo esc_html( date_i18n( 'M j', strtotime( $moon_phase->closest->date ) ) ); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <p class="sgu-moon-riseset-error">Unable to retrieve moon data.</p>
    <?php endif; ?>

</div>
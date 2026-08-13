<?php
/**
 * Template: Planet Positions
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
 * @var array $planets
 * @var bool $has_credentials
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-planet-positions-container" <?php echo $wrapper_attr; ?>>

    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-planet-positions-title"><?php echo esc_html( $title ); ?></h2>
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

    <?php if ( ! $has_credentials ) : ?>
        <div class="sgu-planet-positions-notice">
            <p>Configure AstronomyAPI credentials to display planet positions.</p>
        </div>
    <?php elseif ( ! empty( $planets ) ) : ?>
        <div class="sgu-planet-positions-card">
            <div class="sgu-planet-positions-list">
                <?php foreach ( $planets as $planet ) : ?>
                    <div class="sgu-planet-positions-row <?php echo $planet->visible ? 'sgu-planet-visible' : 'sgu-planet-hidden'; ?>">
                        <span class="sgu-planet-positions-name"><?php echo esc_html( $planet->name ); ?></span>
                        <span class="sgu-planet-positions-status">
                            <?php if ( $planet->visible ) : ?>
                                <?php echo esc_html( round( $planet->altitude ) ); ?>° alt
                            <?php else : ?>
                                Below horizon
                            <?php endif; ?>
                        </span>
                        <?php if ( $planet->constellation ) : ?>
                            <span class="sgu-planet-positions-constellation">in <?php echo esc_html( $planet->constellation ); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <p class="sgu-planet-positions-error">Unable to retrieve planet data.</p>
    <?php endif; ?>

</div>
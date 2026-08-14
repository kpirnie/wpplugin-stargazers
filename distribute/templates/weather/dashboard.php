<?php
/**
 * Template: Weather Dashboard
 * 
 * Displays comprehensive weather dashboard with all components
 * Uses existing templates for each section
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_current Whether to show current weather
 * @var bool $show_hourly Whether to show hourly forecast
 * @var bool $show_daily Whether to show daily forecast
 * @var bool $show_daily_extended Whether to show extended daily forecast
 * @var bool $show_alerts Whether to show weather alerts
 * @var bool $show_noaa Whether to show NOAA forecast
 * @var bool $show_location_picker Whether to show location picker
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $current_weather Current weather data
 * @var object|null $hourly_forecast Hourly forecast data
 * @var object|null $daily_forecast Daily forecast data
 * @var object|null $noaa_forecast NOAA detailed forecast
 * @var array $alerts Weather alerts array
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

/**
 * Helper to load a weather template partial
 */
function sgu_load_weather_template( string $template, array $args = [] ) : void {
    
    // Check theme for override first
    $theme_template = locate_template( [
        "templates/weather/$template",
        "sgu/weather/$template",
        "stargazers/weather/$template",
    ] );

    $file = $theme_template ?: SGUP_PATH . "/templates/weather/$template";

    if ( file_exists( $file ) ) {
        extract( $args );
        include $file;
    }
}
?>

<div class="sgu-weather-dashboard">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <header class="sgu-dashboard-header">
            <h2 class="sgu-dashboard-title"><?php echo esc_html( $title ); ?></h2>
        </header>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <div class="sgu-dashboard-location">
            <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-dashboard-no-location">
            <div class="sgu-weather-location-prompt active">
                <div class="sgu-location-prompt-content">
                    <span class="sgu-location-icon" aria-hidden="true">📍</span>
                    <h3><?php esc_html_e( 'Set Your Location', 'sgup' ); ?></h3>
                    <p><?php esc_html_e( 'Please set your location to view your personalized weather dashboard.', 'sgup' ); ?></p>
                </div>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-dashboard-content">
            
            <?php if ( $show_alerts && ! empty( $alerts ) ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-alerts-section">
                    <?php 
                    sgu_load_weather_template( 'alerts.php', [
                        'title' => __( 'Weather Alerts', 'sgup' ),
                        'show_title' => false,
                        'show_location_picker' => false,
                        'max_alerts' => 5,
                        'has_location' => $has_location,
                        'location' => $location,
                        'alerts' => $alerts,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
            <?php if ( $show_current && $current_weather ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-current-section">
                    <?php 
                    sgu_load_weather_template( 'current.php', [
                        'title' => __( 'Current Weather', 'sgup' ),
                        'show_title' => false,
                        'show_location_picker' => false,
                        'show_details' => true,
                        'has_location' => $has_location,
                        'location' => $location,
                        'weather' => $current_weather,
                        'location_name' => $location_name ?? null,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
            <?php if ( $show_noaa && $noaa_forecast ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-noaa-section">
                    <?php 
                    sgu_load_weather_template( 'day.php', [
                        'title' => __( 'Today\'s Forecast', 'sgup' ),
                        'show_title' => true,
                        'show_location_picker' => false,
                        'show_hourly' => false,
                        'hours_to_show' => 0,
                        'use_noaa' => true,
                        'has_location' => $has_location,
                        'location' => $location,
                        'forecast' => null,
                        'noaa_forecast' => $noaa_forecast,
                        'location_name' => $location_name ?? null,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
            <?php if ( $show_hourly && $hourly_forecast ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-hourly-section">
                    <?php 
                    sgu_load_weather_template( 'hourly.php', [
                        'title' => __( 'Hourly Forecast', 'sgup' ),
                        'show_title' => true,
                        'show_location_picker' => false,
                        'hours_to_show' => 24,
                        'has_location' => $has_location,
                        'location' => $location,
                        'forecast' => $hourly_forecast,
                        'location_name' => $location_name ?? null,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
            <?php if ( $show_daily && $daily_forecast ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-daily-section">
                    <?php 
                    sgu_load_weather_template( 'week.php', [
                        'title' => __( '7-Day Forecast', 'sgup' ),
                        'show_title' => true,
                        'show_location_picker' => false,
                        'days_to_show' => 7,
                        'use_noaa' => false,
                        'has_location' => $has_location,
                        'location' => $location,
                        'forecast' => $daily_forecast,
                        'noaa_forecast' => null,
                        'location_name' => $location_name ?? null,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
            <?php if ( $show_daily_extended && $noaa_forecast ) : ?>
                <section class="sgu-dashboard-section sgu-dashboard-extended-section">
                    <?php 
                    sgu_load_weather_template( 'week-extended.php', [
                        'title' => __( 'Extended Forecast', 'sgup' ),
                        'show_title' => true,
                        'show_location_picker' => false,
                        'has_location' => $has_location,
                        'location' => $location,
                        'weather' => $noaa_forecast,
                        'location_name' => $location_name ?? null,
                    ] );
                    ?>
                </section>
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
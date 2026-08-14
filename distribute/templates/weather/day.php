<?php
/**
 * Template: Daily Forecast
 * 
 * Displays today's detailed forecast with optional hourly breakdown
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var bool $show_hourly Whether to show hourly breakdown
 * @var int $hours_to_show Number of hours to display
 * @var bool $use_noaa Whether to include NOAA forecast
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $forecast Hourly forecast data
 * @var object|null $noaa_forecast NOAA detailed forecast
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-weather-container sgu-weather-daily">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view the forecast.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( ! $forecast && ! $noaa_forecast ) : ?>
        
        <div class="sgu-weather-error">
            <p><?php esc_html_e( 'Unable to retrieve forecast data. Please try again later.', 'sgup' ); ?></p>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-weather-content">
            
            <?php if ( $use_noaa && $noaa_forecast && ! empty( $noaa_forecast->periods ) ) : ?>
                
                <div class="sgu-noaa-forecast">
                    
                    <?php 
                    // Get today's periods (usually "Today" and "Tonight" or similar)
                    $today_periods = array_slice( $noaa_forecast->periods, 0, 2 );
                    ?>
                    
                    <div class="sgu-noaa-periods">
                        
                        <?php foreach ( $today_periods as $period ) : ?>
                            
                            <div class="sgu-noaa-period <?php echo ! ( $period['isDaytime'] ?? true ) ? 'sgu-noaa-night' : 'sgu-noaa-day'; ?>">
                                
                                <h3 class="sgu-noaa-period-name"><?php echo esc_html( $period['name'] ?? '' ); ?></h3>
                                
                                <div class="sgu-noaa-period-main">
                                    <?php if ( ! empty( $period['icon'] ) ) : ?>
                                        <img 
                                            src="<?php echo esc_url( $period['icon'] ); ?>" 
                                            alt="<?php echo esc_attr( $period['shortForecast'] ?? '' ); ?>"
                                            class="sgu-noaa-icon"
                                            loading="lazy"
                                        />
                                    <?php endif; ?>
                                    
                                    <div class="sgu-noaa-temp">
                                        <span class="sgu-temp-value"><?php echo esc_html( $period['temperature'] ?? '' ); ?></span>
                                        <span class="sgu-temp-unit">°<?php echo esc_html( $period['temperatureUnit'] ?? 'F' ); ?></span>
                                    </div>
                                </div>
                                
                                <p class="sgu-noaa-short"><?php echo esc_html( $period['shortForecast'] ?? '' ); ?></p>
                                
                                <?php if ( ! empty( $period['detailedForecast'] ) ) : ?>
                                    <details class="sgu-noaa-details">
                                        <summary><?php esc_html_e( 'Detailed Forecast', 'sgup' ); ?></summary>
                                        <p><?php echo esc_html( $period['detailedForecast'] ); ?></p>
                                    </details>
                                <?php endif; ?>
                                
                                <?php if ( ! empty( $period['windSpeed'] ) ) : ?>
                                    <div class="sgu-noaa-wind">
                                        <span class="sgu-wind-icon">💨</span>
                                        <?php echo esc_html( $period['windSpeed'] ); ?>
                                        <?php if ( ! empty( $period['windDirection'] ) ) : ?>
                                            <?php echo esc_html( $period['windDirection'] ); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                            
                        <?php endforeach; ?>
                        
                    </div>
                    
                </div>
                
            <?php endif; ?>
            
            <?php if ( $show_hourly && $forecast && ! empty( $forecast->hourly ) ) : ?>
                
                <div class="sgu-hourly-forecast">
                    
                    <h3 class="sgu-hourly-title"><?php esc_html_e( 'Hourly Breakdown', 'sgup' ); ?></h3>
                    
                    <div class="sgu-hourly-scroll">
                        <div class="sgu-hourly-list">
                            
                            <?php 
                            $hours = array_slice( $forecast->hourly, 0, $hours_to_show );
                            foreach ( $hours as $hour ) : 
                                $hour_time = $hour['dt'] ?? 0;
                                $hour_temp = $hour['temp'] ?? 0;
                                $hour_icon = $hour['weather'][0]['icon'] ?? '01d';
                                $hour_desc = $hour['weather'][0]['description'] ?? '';
                                $hour_pop = isset( $hour['pop'] ) ? round( $hour['pop'] * 100 ) : 0;
                            ?>
                                
                                <div class="sgu-hourly-item">
                                    <span class="sgu-hourly-time"><?php echo esc_html( date( 'g A', $hour_time ) ); ?></span>
                                    <img 
                                        src="<?php echo esc_url( SGU_Static::get_weather_icon_url( $hour_icon ) ); ?>" 
                                        alt="<?php echo esc_attr( $hour_desc ); ?>"
                                        class="sgu-hourly-icon"
                                        loading="lazy"
                                    />
                                    <span class="sgu-hourly-temp"><?php echo esc_html( round( $hour_temp ) ); ?>°</span>
                                    <?php if ( $hour_pop > 0 ) : ?>
                                        <span class="sgu-hourly-precip">
                                            <span class="sgu-precip-icon">💧</span>
                                            <?php echo esc_html( $hour_pop ); ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                            <?php endforeach; ?>
                            
                        </div>
                    </div>
                    
                </div>
                
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
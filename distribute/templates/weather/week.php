<?php
/**
 * Template: Weekly Forecast
 * 
 * Displays 7-day weather forecast
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var int $days_to_show Number of days to display (max 8)
 * @var bool $use_noaa Whether to include NOAA forecast
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $forecast Daily forecast data
 * @var object|null $noaa_forecast NOAA detailed forecast
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-weather-container sgu-weather-weekly">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view the weekly forecast.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( ! $forecast || empty( $forecast->daily ) ) : ?>
        
        <div class="sgu-weather-error">
            <p><?php esc_html_e( 'Unable to retrieve forecast data. Please try again later.', 'sgup' ); ?></p>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-weather-content">
            
            <div class="sgu-weekly-forecast">
                
                <div class="sgu-weekly-list">
                    
                    <?php 
                    $days = array_slice( $forecast->daily, 0, $days_to_show );
                    
                    foreach ( $days as $index => $day ) : 
                        $day_time = $day['dt'] ?? 0;
                        $day_name = date( 'l', $day_time );
                        $day_date = date( 'M j', $day_time );
                        $temp_max = $day['temp']['max'] ?? 0;
                        $temp_min = $day['temp']['min'] ?? 0;
                        $day_icon = $day['weather'][0]['icon'] ?? '01d';
                        $day_desc = $day['weather'][0]['description'] ?? '';
                        $day_summary = $day['summary'] ?? $day_desc;
                        $day_pop = isset( $day['pop'] ) ? round( $day['pop'] * 100 ) : 0;
                        $day_humidity = $day['humidity'] ?? 0;
                        $day_wind = $day['wind_speed'] ?? 0;
                        $day_wind_deg = $day['wind_deg'] ?? 0;
                        $day_uvi = $day['uvi'] ?? 0;
                        $day_sunrise = $day['sunrise'] ?? 0;
                        $day_sunset = $day['sunset'] ?? 0;
                        
                        // Determine if this is today
                        $is_today = ( date( 'Y-m-d', $day_time ) === date( 'Y-m-d' ) );
                    ?>
                        
                        <div class="sgu-weekly-day <?php echo $is_today ? 'sgu-weekly-today' : ''; ?>">
                            
                            <div class="sgu-weekly-day-header">
                                <span class="sgu-weekly-day-name">
                                    <?php echo $is_today ? esc_html__( 'Today', 'sgup' ) : esc_html( $day_name ); ?>
                                </span>
                                <span class="sgu-weekly-day-date"><?php echo esc_html( $day_date ); ?></span>
                            </div>
                            
                            <div class="sgu-weekly-day-main">
                                
                                <div class="sgu-weekly-icon-wrap">
                                    <img 
                                        src="<?php echo esc_url( SGU_Static::get_weather_icon_url( $day_icon ) ); ?>" 
                                        alt="<?php echo esc_attr( $day_desc ); ?>"
                                        class="sgu-weekly-icon"
                                        loading="lazy"
                                    />
                                </div>
                                
                                <div class="sgu-weekly-temps">
                                    <span class="sgu-weekly-temp-high"><?php echo esc_html( round( $temp_max ) ); ?>°</span>
                                    <span class="sgu-weekly-temp-sep">/</span>
                                    <span class="sgu-weekly-temp-low"><?php echo esc_html( round( $temp_min ) ); ?>°</span>
                                </div>
                                
                            </div>
                            
                            <div class="sgu-weekly-day-desc">
                                <span><?php echo esc_html( ucwords( $day_desc ) ); ?></span>
                            </div>
                            
                            <div class="sgu-weekly-day-details">
                                
                                <?php if ( $day_pop > 0 ) : ?>
                                    <span class="sgu-weekly-detail" title="<?php esc_attr_e( 'Precipitation Chance', 'sgup' ); ?>">
                                        <span class="sgu-detail-icon">💧</span>
                                        <?php echo esc_html( $day_pop ); ?>%
                                    </span>
                                <?php endif; ?>
                                
                                <span class="sgu-weekly-detail" title="<?php esc_attr_e( 'Wind', 'sgup' ); ?>">
                                    <span class="sgu-detail-icon">💨</span>
                                    <?php echo esc_html( round( $day_wind ) ); ?> mph
                                </span>
                                
                                <?php if ( $day_uvi > 0 ) : ?>
                                    <?php 
                                    $uvi_class = 'low';
                                    if ( $day_uvi >= 8 ) $uvi_class = 'very-high';
                                    elseif ( $day_uvi >= 6 ) $uvi_class = 'high';
                                    elseif ( $day_uvi >= 3 ) $uvi_class = 'moderate';
                                    ?>
                                    <span class="sgu-weekly-detail sgu-uvi-<?php echo esc_attr( $uvi_class ); ?>" title="<?php esc_attr_e( 'UV Index', 'sgup' ); ?>">
                                        <span class="sgu-detail-icon">☀️</span>
                                        <?php echo esc_html( round( $day_uvi ) ); ?>
                                    </span>
                                <?php endif; ?>
                                
                            </div>
                            
                            <?php if ( $day_sunrise && $day_sunset ) : ?>
                                <div class="sgu-weekly-sun">
                                    <span class="sgu-sun-rise" title="<?php esc_attr_e( 'Sunrise', 'sgup' ); ?>">
                                        🌅 <?php echo esc_html( date( 'g:i A', $day_sunrise ) ); ?>
                                    </span>
                                    <span class="sgu-sun-set" title="<?php esc_attr_e( 'Sunset', 'sgup' ); ?>">
                                        🌇 <?php echo esc_html( date( 'g:i A', $day_sunset ) ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                        </div>
                        
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
            <?php if ( $use_noaa && $noaa_forecast && ! empty( $noaa_forecast->periods ) ) : ?>
                
                <div class="sgu-noaa-extended">
                    
                    <h3 class="sgu-noaa-title"><?php esc_html_e( 'Extended Forecast Details', 'sgup' ); ?></h3>
                    
                    <div class="sgu-noaa-periods-extended">
                        
                        <?php foreach ( $noaa_forecast->periods as $period ) : ?>
                            
                            <div class="sgu-noaa-period-extended <?php echo ! ( $period['isDaytime'] ?? true ) ? 'sgu-noaa-night' : 'sgu-noaa-day'; ?>">
                                
                                <div class="sgu-noaa-period-header">
                                    <h4><?php echo esc_html( $period['name'] ?? '' ); ?></h4>
                                    <span class="sgu-noaa-period-temp">
                                        <?php echo esc_html( $period['temperature'] ?? '' ); ?>°<?php echo esc_html( $period['temperatureUnit'] ?? 'F' ); ?>
                                    </span>
                                </div>
                                
                                <?php if ( ! empty( $period['detailedForecast'] ) ) : ?>
                                    <p class="sgu-noaa-detailed"><?php echo esc_html( $period['detailedForecast'] ); ?></p>
                                <?php endif; ?>
                                
                            </div>
                            
                        <?php endforeach; ?>
                        
                    </div>
                    
                </div>
                
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
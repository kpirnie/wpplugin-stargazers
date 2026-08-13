<?php
/**
 * Template: Hourly Forecast
 * 
 * Displays today's hourly weather forecast
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var int $hours_to_show Number of hours to display
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $forecast Hourly forecast data
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-weather-container sgu-weather-hourly">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view the hourly forecast.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( ! $forecast || empty( $forecast->hourly ) ) : ?>
        
        <div class="sgu-weather-error">
            <p><?php esc_html_e( 'Unable to retrieve hourly forecast data. Please try again later.', 'sgup' ); ?></p>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-weather-content">
            
            <div class="sgu-hourly-forecast-full">
                
                <div class="sgu-hourly-scroll">
                    <div class="sgu-hourly-list">
                        
                        <?php 
                        $hours = array_slice( $forecast->hourly, 0, $hours_to_show );
                        $current_date = '';
                        
                        foreach ( $hours as $index => $hour ) : 
                            $hour_time = $hour['dt'] ?? 0;
                            $hour_date = date( 'l, M j', $hour_time );
                            $hour_temp = $hour['temp'] ?? 0;
                            $hour_feels = $hour['feels_like'] ?? $hour_temp;
                            $hour_icon = $hour['weather'][0]['icon'] ?? '01d';
                            $hour_desc = $hour['weather'][0]['description'] ?? '';
                            $hour_pop = isset( $hour['pop'] ) ? round( $hour['pop'] * 100 ) : 0;
                            $hour_humidity = $hour['humidity'] ?? 0;
                            $hour_wind = $hour['wind_speed'] ?? 0;
                            $hour_wind_deg = $hour['wind_deg'] ?? 0;
                            $hour_clouds = $hour['clouds'] ?? 0;
                            
                            // Check if we need a date separator
                            $show_date_header = ( $hour_date !== $current_date );
                            if ( $show_date_header ) {
                                $current_date = $hour_date;
                            }
                        ?>
                            
                            <?php if ( $show_date_header ) : ?>
                                <div class="sgu-hourly-date-header">
                                    <span><?php echo esc_html( $hour_date ); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="sgu-hourly-item sgu-hourly-item-detailed">
                                
                                <div class="sgu-hourly-time-col">
                                    <span class="sgu-hourly-time"><?php echo esc_html( date( 'g:i A', $hour_time ) ); ?></span>
                                </div>
                                
                                <div class="sgu-hourly-icon-col">
                                    <img 
                                        src="<?php echo esc_url( SGU_Static::get_weather_icon_url( $hour_icon ) ); ?>" 
                                        alt="<?php echo esc_attr( $hour_desc ); ?>"
                                        class="sgu-hourly-icon"
                                        loading="lazy"
                                    />
                                </div>
                                
                                <div class="sgu-hourly-temp-col">
                                    <span class="sgu-hourly-temp"><?php echo esc_html( round( $hour_temp ) ); ?>°F</span>
                                    <span class="sgu-hourly-feels">
                                        <?php esc_html_e( 'Feels', 'sgup' ); ?> <?php echo esc_html( round( $hour_feels ) ); ?>°
                                    </span>
                                </div>
                                
                                <div class="sgu-hourly-desc-col">
                                    <span class="sgu-hourly-desc"><?php echo esc_html( ucwords( $hour_desc ) ); ?></span>
                                </div>
                                
                                <div class="sgu-hourly-details-col">
                                    
                                    <?php if ( $hour_pop > 0 ) : ?>
                                        <span class="sgu-hourly-detail sgu-hourly-precip" title="<?php esc_attr_e( 'Precipitation Chance', 'sgup' ); ?>">
                                            <span class="sgu-detail-icon">💧</span>
                                            <?php echo esc_html( $hour_pop ); ?>%
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="sgu-hourly-detail sgu-hourly-humidity" title="<?php esc_attr_e( 'Humidity', 'sgup' ); ?>">
                                        <span class="sgu-detail-icon">💦</span>
                                        <?php echo esc_html( $hour_humidity ); ?>%
                                    </span>
                                    
                                    <span class="sgu-hourly-detail sgu-hourly-wind" title="<?php esc_attr_e( 'Wind', 'sgup' ); ?>">
                                        <span class="sgu-detail-icon">💨</span>
                                        <?php echo esc_html( round( $hour_wind ) ); ?> mph
                                        <?php echo esc_html( SGU_Static::wind_direction_to_compass( $hour_wind_deg ) ); ?>
                                    </span>
                                    
                                    <span class="sgu-hourly-detail sgu-hourly-clouds" title="<?php esc_attr_e( 'Cloud Cover', 'sgup' ); ?>">
                                        <span class="sgu-detail-icon">☁️</span>
                                        <?php echo esc_html( $hour_clouds ); ?>%
                                    </span>
                                    
                                </div>
                                
                            </div>
                            
                        <?php endforeach; ?>
                        
                    </div>
                </div>
                
            </div>
            
        </div>
        
    <?php endif; ?>
    
</div>
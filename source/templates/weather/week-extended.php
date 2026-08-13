<?php
/**
 * Template: Weekly Extended Forecast
 * 
 * Displays 7-day extended forecast from NOAA
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $weather NOAA forecast data
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-weather-container sgu-weather-weekly-extended">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view the extended forecast.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( ! $weather || empty( $weather->periods ) ) : ?>
        
        <div class="sgu-weather-error">
            <p><?php esc_html_e( 'Unable to retrieve extended forecast data. Please try again later.', 'sgup' ); ?></p>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-weather-content">
            
            <?php if ( ! empty( $weather->location ) ) : ?>
                <div class="sgu-extended-location">
                    <span class="sgu-location-icon">📍</span>
                    <span class="sgu-location-name">
                        <?php 
                        $loc_parts = [];
                        if ( ! empty( $weather->location->city ) ) $loc_parts[] = $weather->location->city;
                        if ( ! empty( $weather->location->state ) ) $loc_parts[] = $weather->location->state;
                        echo esc_html( implode( ', ', $loc_parts ) );
                        ?>
                    </span>
                    <?php if ( ! empty( $weather->location->gridId ) ) : ?>
                        <span class="sgu-location-grid">
                            (<?php echo esc_html( $weather->location->gridId ); ?> <?php echo esc_html( $weather->location->gridX ); ?>,<?php echo esc_html( $weather->location->gridY ); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="sgu-extended-forecast">
                
                <div class="sgu-extended-periods">
                    
                    <?php foreach ( $weather->periods as $index => $period ) : ?>
                        
                        <?php
                        $is_daytime = $period['isDaytime'] ?? true;
                        $period_name = $period['name'] ?? '';
                        $temperature = $period['temperature'] ?? '';
                        $temp_unit = $period['temperatureUnit'] ?? 'F';
                        $temp_trend = $period['temperatureTrend'] ?? null;
                        $wind_speed = $period['windSpeed'] ?? '';
                        $wind_direction = $period['windDirection'] ?? '';
                        $icon_url = $period['icon'] ?? '';
                        $short_forecast = $period['shortForecast'] ?? '';
                        $detailed_forecast = $period['detailedForecast'] ?? '';
                        $prob_precipitation = $period['probabilityOfPrecipitation']['value'] ?? null;
                        $dewpoint = $period['dewpoint']['value'] ?? null;
                        $humidity = $period['relativeHumidity']['value'] ?? null;
                        ?>
                        
                        <article class="sgu-extended-period <?php echo $is_daytime ? 'sgu-period-day' : 'sgu-period-night'; ?>">
                            
                            <header class="sgu-extended-period-header">
                                
                                <div class="sgu-period-name-wrap">
                                    <h3 class="sgu-period-name"><?php echo esc_html( $period_name ); ?></h3>
                                    <span class="sgu-period-type">
                                        <?php echo $is_daytime ? esc_html__( 'Daytime', 'sgup' ) : esc_html__( 'Overnight', 'sgup' ); ?>
                                    </span>
                                </div>
                                
                                <div class="sgu-period-temp-wrap">
                                    <span class="sgu-period-temp">
                                        <?php echo esc_html( $temperature ); ?>°<?php echo esc_html( $temp_unit ); ?>
                                    </span>
                                    <?php if ( $temp_trend ) : ?>
                                        <span class="sgu-temp-trend sgu-trend-<?php echo esc_attr( strtolower( $temp_trend ) ); ?>">
                                            <?php echo esc_html( $temp_trend ); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                            </header>
                            
                            <div class="sgu-extended-period-body">
                                
                                <div class="sgu-period-main">
                                    
                                    <?php if ( ! empty( $icon_url ) ) : ?>
                                        <div class="sgu-period-icon-wrap">
                                            <img 
                                                src="<?php echo esc_url( $icon_url ); ?>" 
                                                alt="<?php echo esc_attr( $short_forecast ); ?>"
                                                class="sgu-period-icon"
                                                loading="lazy"
                                            />
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="sgu-period-summary">
                                        <span class="sgu-short-forecast"><?php echo esc_html( $short_forecast ); ?></span>
                                    </div>
                                    
                                </div>
                                
                                <div class="sgu-period-details">
                                    
                                    <?php if ( ! empty( $wind_speed ) ) : ?>
                                        <div class="sgu-period-detail">
                                            <span class="sgu-detail-icon">💨</span>
                                            <span class="sgu-detail-label"><?php esc_html_e( 'Wind', 'sgup' ); ?></span>
                                            <span class="sgu-detail-value">
                                                <?php echo esc_html( $wind_speed ); ?>
                                                <?php if ( ! empty( $wind_direction ) ) : ?>
                                                    <?php echo esc_html( $wind_direction ); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $prob_precipitation !== null ) : ?>
                                        <div class="sgu-period-detail">
                                            <span class="sgu-detail-icon">💧</span>
                                            <span class="sgu-detail-label"><?php esc_html_e( 'Precip Chance', 'sgup' ); ?></span>
                                            <span class="sgu-detail-value"><?php echo esc_html( $prob_precipitation ); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $humidity !== null ) : ?>
                                        <div class="sgu-period-detail">
                                            <span class="sgu-detail-icon">💦</span>
                                            <span class="sgu-detail-label"><?php esc_html_e( 'Humidity', 'sgup' ); ?></span>
                                            <span class="sgu-detail-value"><?php echo esc_html( $humidity ); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $dewpoint !== null ) : ?>
                                        <div class="sgu-period-detail">
                                            <span class="sgu-detail-icon">🌡️</span>
                                            <span class="sgu-detail-label"><?php esc_html_e( 'Dewpoint', 'sgup' ); ?></span>
                                            <span class="sgu-detail-value"><?php echo esc_html( round( $dewpoint ) ); ?>°F</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                </div>
                                
                                <?php if ( ! empty( $detailed_forecast ) ) : ?>
                                    <div class="sgu-period-detailed">
                                        <p><?php echo esc_html( $detailed_forecast ); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                            </div>
                            
                        </article>
                        
                    <?php endforeach; ?>
                    
                </div>
                
            </div>
            
            <?php if ( ! empty( $weather->generatedAt ) || ! empty( $weather->updateTime ) ) : ?>
                <div class="sgu-extended-meta">
                    <?php if ( ! empty( $weather->updateTime ) ) : ?>
                        <small>
                            <?php esc_html_e( 'Last updated:', 'sgup' ); ?> 
                            <?php echo esc_html( date( 'F j, Y g:i A', strtotime( $weather->updateTime ) ) ); ?>
                        </small>
                    <?php endif; ?>
                    <small class="sgu-noaa-credit">
                        <?php esc_html_e( 'Data provided by NOAA/National Weather Service', 'sgup' ); ?>
                    </small>
                </div>
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
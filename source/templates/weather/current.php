<?php
/**
 * Template: Current Weather
 * 
 * Displays current weather conditions
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $title The block title
 * @var bool $show_title Whether to show the title
 * @var bool $show_location_picker Whether to show location picker
 * @var bool $show_details Whether to show detailed weather info
 * @var bool $has_location Whether user has a stored location
 * @var object|null $location User's location data
 * @var object|null $weather Current weather data
 * @var object|null $location_name Location name from reverse geocoding
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );
?>

<div class="sgu-weather-container sgu-weather-current">
    
    <?php if ( $show_title && ! empty( $title ) ) : ?>
        <h2 class="sgu-weather-title"><?php echo esc_html( $title ); ?></h2>
    <?php endif; ?>

    <?php if ( $show_location_picker ) : ?>
        <?php include __DIR__ . '/partials/location-picker-inline.php'; ?>
    <?php endif; ?>

    <?php if ( ! $has_location ) : ?>
        
        <div class="sgu-weather-no-location">
            <div class="sgu-weather-location-prompt active">
                <p><?php esc_html_e( 'Please set your location to view weather information.', 'sgup' ); ?></p>
                <?php include __DIR__ . '/partials/location-form.php'; ?>
            </div>
        </div>
        
    <?php elseif ( ! $weather ) : ?>
        
        <div class="sgu-weather-error">
            <p><?php esc_html_e( 'Unable to retrieve weather data. Please try again later.', 'sgup' ); ?></p>
        </div>
        
    <?php else : ?>
        
        <div class="sgu-weather-content">
            
            <div class="sgu-current-main">
                
                <div class="sgu-current-icon">
                    <?php 
                    $icon = $weather->weather[0]->icon ?? '01d';
                    $description = $weather->weather[0]->description ?? '';
                    ?>
                    <img 
                        src="<?php echo esc_url( SGU_Static::get_weather_icon_url( $icon ) ); ?>" 
                        alt="<?php echo esc_attr( $description ); ?>"
                        class="sgu-weather-icon"
                        loading="lazy"
                    />
                </div>
                
                <div class="sgu-current-temp">
                    <span class="sgu-temp-value"><?php echo esc_html( round( $weather->main->temp ) ); ?></span>
                    <span class="sgu-temp-unit">°F</span>
                </div>
                
                <div class="sgu-current-description">
                    <span class="sgu-weather-condition"><?php echo esc_html( ucwords( $description ) ); ?></span>
                    <?php if ( isset( $weather->main->feels_like ) ) : ?>
                        <span class="sgu-feels-like">
                            <?php esc_html_e( 'Feels like', 'sgup' ); ?> 
                            <?php echo esc_html( round( $weather->main->feels_like ) ); ?>°F
                        </span>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <?php if ( $show_details ) : ?>
                
                <div class="sgu-current-details">
                    
                    <div class="sgu-detail-grid">
                        
                        <?php if ( isset( $weather->main->humidity ) ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">💧</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Humidity', 'sgup' ); ?></span>
                                <span class="sgu-detail-value"><?php echo esc_html( $weather->main->humidity ); ?>%</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $weather->wind->speed ) ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">💨</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Wind', 'sgup' ); ?></span>
                                <span class="sgu-detail-value">
                                    <?php echo esc_html( round( $weather->wind->speed ) ); ?> mph
                                    <?php if ( isset( $weather->wind->deg ) ) : ?>
                                        <?php echo esc_html( SGU_Static::wind_direction_to_compass( $weather->wind->deg ) ); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $weather->main->pressure ) ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">📊</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Pressure', 'sgup' ); ?></span>
                                <span class="sgu-detail-value"><?php echo esc_html( $weather->main->pressure ); ?> hPa</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $weather->clouds->all ) ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">☁️</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Cloud Cover', 'sgup' ); ?></span>
                                <span class="sgu-detail-value"><?php echo esc_html( $weather->clouds->all ); ?>%</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $weather->visibility ) && $weather->visibility !== null ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">👁️</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Visibility', 'sgup' ); ?></span>
                                <span class="sgu-detail-value"><?php echo esc_html( round( $weather->visibility / 1609.34, 1 ) ); ?> mi</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ( isset( $weather->wind->gust ) && $weather->wind->gust > 0 ) : ?>
                            <div class="sgu-detail-item">
                                <span class="sgu-detail-icon">🌬️</span>
                                <span class="sgu-detail-label"><?php esc_html_e( 'Wind Gusts', 'sgup' ); ?></span>
                                <span class="sgu-detail-value"><?php echo esc_html( round( $weather->wind->gust ) ); ?> mph</span>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                    
                    <?php if ( isset( $weather->sys->sunrise ) && isset( $weather->sys->sunset ) ) : ?>
                        <div class="sgu-sun-times">
                            <div class="sgu-sun-item">
                                <span class="sgu-sun-icon">🌅</span>
                                <span class="sgu-sun-label"><?php esc_html_e( 'Sunrise', 'sgup' ); ?></span>
                                <span class="sgu-sun-value"><?php echo esc_html( date( 'g:i A', $weather->sys->sunrise ) ); ?></span>
                            </div>
                            <div class="sgu-sun-item">
                                <span class="sgu-sun-icon">🌇</span>
                                <span class="sgu-sun-label"><?php esc_html_e( 'Sunset', 'sgup' ); ?></span>
                                <span class="sgu-sun-value"><?php echo esc_html( date( 'g:i A', $weather->sys->sunset ) ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            <?php endif; ?>
            
            <?php if ( isset( $weather->dt ) ) : ?>
                <div class="sgu-weather-updated">
                    <small>
                        <?php esc_html_e( 'Last updated:', 'sgup' ); ?> 
                        <?php echo esc_html( date( 'g:i A', $weather->dt ) ); ?>
                    </small>
                </div>
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
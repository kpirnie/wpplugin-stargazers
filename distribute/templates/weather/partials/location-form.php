<?php

/**
 * Partial: Location Form
 * 
 * The geolocation and zip code entry form
 * 
 * @package US Star Gazers
 * @since 8.4
 */

// Prevent direct access
defined('ABSPATH') || die('No direct script access allowed');

$zip_field_id = wp_unique_id('sgu-weather-zip-');
?>

<div class="sgu-location-form">

    <div class="sgu-location-form-geolocate">
        <button type="button" class="sgu-weather-geolocate">
            <span class="sgu-btn-icon" aria-hidden="true">📍</span>
            <span class="sgu-btn-text"><?php esc_html_e('Use My Location', 'sgup'); ?></span>
        </button>
    </div>

    <div class="sgu-location-form-divider">
        <span><?php esc_html_e('or', 'sgup'); ?></span>
    </div>

    <form class="sgu-weather-zip-form" method="post" action="">
        <label class="screen-reader-text" for="sgu-weather-zip-<?php echo esc_attr(wp_unique_id()); ?>">
            <?php esc_html_e('ZIP Code', 'sgup'); ?>
        </label>
        <input
            type="text"
            class="sgu-weather-zip-input"
            id="<?php echo esc_attr($zip_field_id); ?>"
            inputmode="numeric"
            pattern="\d{5}"
            maxlength="5"
            placeholder="<?php esc_attr_e('Enter ZIP Code', 'sgup'); ?>"
            aria-label="<?php esc_attr_e('ZIP Code', 'sgup'); ?>" />
        <button type="submit"><?php esc_html_e('Go', 'sgup'); ?></button>
    </form>

    <button type="button" class="sgu-weather-cancel-change">
        <?php esc_html_e('Cancel', 'sgup'); ?>
    </button>

</div>
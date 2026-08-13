<?php
/**
 * Template: Space Weather Alerts
 * 
 * Displays Space Weather alerts with pagination
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var bool $show_paging Whether to show pagination
 * @var int $max_pages Maximum number of pages
 * @var int $per_page Items per page
 * @var string $paging_location Pagination location (top, bottom, both)
 * @var int $paged Current page number
 * @var object $data WP_Query object containing posts
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Early return if no data
if ( ! $data || ! $data->posts ) {
    return;
}

// Pagination helper
$render_pagination = function() use ( $max_pages, $paged ) {
    echo SGU_Static::cpt_pagination( $max_pages, $paged );
};
?>

<div class="sgu-sw-alerts">
    
    <?php if ( $show_paging && in_array( $paging_location, [ 'top', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-top">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>

    <div class="sgu-alerts-list">
        
        <?php foreach ( $data->posts as $post ) : ?>
            
            <?php
            // Unserialize the content data
            $sw_data = maybe_unserialize( $post->post_content );
            
            // Extract key information
            $product_id = $sw_data['product_id'] ?? '';
            $issue_datetime = isset( $sw_data['issue_datetime'] ) ? strtotime( $sw_data['issue_datetime'] ) : strtotime( $post->post_date );
            $message = $sw_data['message'] ?? '';
            
            // Parse product ID for type identification
            $product_type = '';
            if ( preg_match( '/^([A-Z]+)/', $product_id, $matches ) ) {
                $product_type = $matches[1];
            }
            
            // Product type labels
            $product_labels = [
                'ALTK' => __( 'K-index Alert', 'sgup' ),
                'ALTA' => __( 'A-index Alert', 'sgup' ),
                'ALTEF' => __( 'Electron Flux Alert', 'sgup' ),
                'ALTPX' => __( 'Proton Event Alert', 'sgup' ),
                'ALTTP' => __( 'Type II/IV Alert', 'sgup' ),
                'SUM' => __( 'Summary', 'sgup' ),
                'WAR' => __( 'Warning', 'sgup' ),
                'WAT' => __( 'Watch', 'sgup' ),
            ];
            
            $product_label = $product_labels[ $product_type ] ?? __( 'Space Weather Alert', 'sgup' );
            
            // Determine severity from message content
            $severity = 'normal';
            $message_lower = strtolower( $message );
            if ( strpos( $message_lower, 'warning' ) !== false || strpos( $message_lower, 'extreme' ) !== false ) {
                $severity = 'high';
            } elseif ( strpos( $message_lower, 'watch' ) !== false || strpos( $message_lower, 'moderate' ) !== false ) {
                $severity = 'medium';
            }
            ?>
            
            <article class="sgu-alert-item sgu-sw-item sgu-severity-<?php echo esc_attr( $severity ); ?>">
                
                <header class="sgu-alert-header">
                    <span class="sgu-alert-icon">🌌</span>
                    <div class="sgu-alert-title-wrap">
                        <h3 class="sgu-alert-title">
                            <span class="sgu-sw-type"><?php echo esc_html( $product_label ); ?></span>
                            <span class="sgu-sw-id"><?php echo esc_html( $product_id ); ?></span>
                        </h3>
                        <time class="sgu-alert-date" datetime="<?php echo esc_attr( date( 'c', $issue_datetime ) ); ?>">
                            <?php echo esc_html( date( 'F j, Y \a\t g:i A', $issue_datetime ) ); ?> UTC
                        </time>
                    </div>
                </header>
                
                <div class="sgu-alert-body">
                    
                    <?php if ( ! empty( $message ) ) : ?>
                        <div class="sgu-sw-message">
                            <?php 
                            // Format the message - preserve line breaks but escape HTML
                            $formatted_message = nl2br( esc_html( $message ) );
                            echo $formatted_message;
                            ?>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
                <?php if ( $severity !== 'normal' ) : ?>
                    <footer class="sgu-alert-footer">
                        <span class="sgu-severity-badge sgu-severity-badge-<?php echo esc_attr( $severity ); ?>">
                            <?php 
                            $severity_labels = [
                                'high' => __( 'High Priority', 'sgup' ),
                                'medium' => __( 'Moderate Priority', 'sgup' ),
                            ];
                            echo esc_html( $severity_labels[ $severity ] ?? '' );
                            ?>
                        </span>
                    </footer>
                <?php endif; ?>
                
            </article>
            
        <?php endforeach; ?>
        
    </div>

    <?php if ( $show_paging && in_array( $paging_location, [ 'bottom', 'both' ] ) ) : ?>
        <div class="sgu-pagination sgu-pagination-bottom">
            <?php $render_pagination(); ?>
        </div>
    <?php endif; ?>
    
</div>
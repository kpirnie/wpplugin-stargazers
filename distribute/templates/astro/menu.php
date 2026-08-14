<?php
/**
 * Template: Astronomy Menu
 * 
 * Displays navigation menu for astronomy sections
 * 
 * @package US Star Gazers
 * @since 8.4
 * 
 * Available variables:
 * @var string $the_menu The rendered WordPress menu HTML
 * @var bool $is_inline Whether to display menu inline
 * @var string $text_align Text alignment (left, right, center)
 */

// Prevent direct access
defined( 'ABSPATH' ) || die( 'No direct script access allowed' );

// Early return if no menu
if ( empty( $the_menu ) ) {
    return;
}

// Build CSS classes
$wrapper_classes = [ 'sgu-astro-menu' ];

if ( $is_inline ) {
    $wrapper_classes[] = 'sgu-menu-inline';
}

$wrapper_classes[] = 'sgu-menu-align-' . esc_attr( $text_align );
?>

<nav class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Astronomy Navigation', 'sgup' ); ?>">
    <ul class="sgu-menu-list">
        <?php echo $the_menu; ?>
    </ul>
</nav>
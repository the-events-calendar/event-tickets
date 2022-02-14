<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since TBD
 *
 * @var Tribe__Template $this             Template object.
 * @var string          $selected_section Slug of the currently selected section.
 * @var array           $classes          Array of classes.
 * @var string          $url              Link URL.
 * @var string          $slug             Slug of the section.
 * @var string          $text             Link text.
 */

if ( empty( $text ) || empty( $url ) ) {
	return;
}

$classes[] = 'tec-tickets__admin-settings-tickets-commerce-section-menu-link';

// Determines if this is an active section
$classes['tec-tickets__admin-settings-tickets-commerce-section-menu-link--active'] = $selected_section === $slug;

?>
<a <?php tribe_classes( $classes ); ?> href="<?php echo esc_attr( $url ); ?>">
	<?php echo esc_html( $text ); ?>
</a>
<?php
/**
 * View: Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/components/loader.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */
if ( empty( $text ) ) {
	$text = $this->get( 'text' ) ?: __( 'Loading...', 'event-tickets' );
}

if ( empty( $classes ) ) {
	$classes = $this->get( 'classes' ) ?: [];
}

$spinner_classes = [
	'tribe-loader',
	'tribe-common-a11y-hidden',
];

if ( ! empty( $classes ) ) {
	$spinner_classes = array_merge( $spinner_classes, (array) $classes );
}

?>
<div class="tribe-common tribe-loader__wrapper">
	<div
	<?php tribe_classes( $spinner_classes ); ?>
		role="alert"
		aria-live="assertive"
	>
		<div class="tribe-loader__spinner">
			<?php echo esc_html( $text ); ?>
		</div>
	</div>
</div>

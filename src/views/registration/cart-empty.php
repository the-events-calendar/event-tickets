<?php
/**
 * This template renders the empty cart for the
 * attendee registration page.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/cart-empty.php
 *
 * @version 4.9
 *
 */
?>
<p><?php esc_html_e( 'You currently have no events awaiting registration', 'event-tickets' ); ?></p>

<?php if ( class_exists( 'Tribe__Events__Main' ) ) : ?>

	<?php
	$link = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( tribe_get_events_link() ),
		esc_html__( 'the calendar', 'event-tickets' )
	);

	$text = __( 'Find events to attend on %1$s', 'event-tickets' );
	?>

	<p><?php printf( $text, $link ); ?></p>

<?php endif;

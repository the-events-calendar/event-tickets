<?php
/**
 * RSVP V2: Not Going Success Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/messages/success/not-going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket    The RSVP ticket object.
 * @var int                           $post_id   The event post ID.
 * @var null|bool                     $is_going  Whether the user confirmed for going or not-going.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

if ( ! empty( $is_going ) ) {
	return;
}
?>

<span class="tribe-tickets__rsvp-v2-message-text">
	<strong>
		<?php esc_html_e( 'Thank you for confirming!', 'event-tickets' ); ?>
	</strong>

	<?php
	echo esc_html(
		sprintf(
			/* Translators: %1$s: RSVP label. */
			_x( 'Your %1$s response has been received.', 'blocks rsvp messages success', 'event-tickets' ),
			tribe_get_rsvp_label_singular( 'blocks_rsvp_messages_success' )
		)
	);
	?>

</span>

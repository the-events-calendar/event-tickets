<?php
/**
 * Block: RSVP
 * Messages Success for Going
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/messages/success/not-going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp     The rsvp ticket object.
 * @var null|bool                     $is_going Whether the user confirmed for going or not-going.
 *
 * @since TBD
 *
 * @version TBD
 */

if ( ! empty( $is_going ) ) {
	return;
}
?>

<span class="tribe-tickets__rsvp-message-text">
	<strong>
		<?php esc_html_e( 'Thank you for confirming!', 'event-tickets' ); ?>
	</strong>

	<?php esc_html_e( 'Your RSVP response has been received', 'event-tickets' ); ?>

</span>

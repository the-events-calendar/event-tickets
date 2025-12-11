<?php
/**
 * RSVP V2: Error Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/messages/error.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket        The RSVP ticket object.
 * @var int                           $post_id       The event post ID.
 * @var string|array                  $error_message The error message(s).
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

// Treat error messages as an array.
$error_messages = (array) $error_message;
?>
<div class="tribe-tickets__rsvp-v2-message tribe-tickets__rsvp-v2-message--error tribe-common-b3">
	<?php $this->template( 'v2/components/icons/error', [ 'classes' => [ 'tribe-tickets__rsvp-v2-message--error-icon' ] ] ); ?>

	<?php foreach ( $error_messages as $message ) : ?>
		<span class="tribe-tickets__rsvp-v2-message-text">
			<strong>
				<?php echo wp_kses_post( $message ); ?>
			</strong>
		</span>
	<?php endforeach; ?>
</div>

<?php
/**
 * RSVP V2: Form Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
 * @var int                           $post_id The event post ID.
 * @var string                        $going   The RSVP status ('going' or 'not-going').
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$going = $this->get( 'going' );
?>

<form
	name="tribe-tickets-rsvp-v2-form"
	class="tribe-tickets__rsvp-v2-form"
	data-rsvp-v2-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-rsvp-v2-post-id="<?php echo esc_attr( $post_id ); ?>"
	data-rsvp-v2-status="<?php echo esc_attr( $going ); ?>"
>
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $ticket->ID ) ); ?>][ticket_id]" value="<?php echo esc_attr( absint( $ticket->ID ) ); ?>">
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $ticket->ID ) ); ?>][attendees][0][order_status]" value="<?php echo esc_attr( $going ); ?>">
	<input type="hidden" name="tribe_tickets[<?php echo esc_attr( absint( $ticket->ID ) ); ?>][attendees][0][optout]" value="1">
	<input type="hidden" name="tribe_rsvp_v2_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tribe_tickets_rsvp_v2' ) ); ?>">

	<div class="tribe-tickets__rsvp-v2-form-wrapper">

		<?php $this->template( 'v2/rsvp-v2/form/title', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] ); ?>

		<div class="tribe-tickets__rsvp-v2-form-content tribe-tickets__form">

			<?php $this->template( 'v2/rsvp-v2/form/fields/fields', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] ); ?>

			<?php $this->template( 'v2/rsvp-v2/form/buttons', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] ); ?>

		</div>

	</div>

</form>

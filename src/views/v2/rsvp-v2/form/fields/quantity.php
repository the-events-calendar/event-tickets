<?php
/**
 * RSVP V2: Quantity Input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/fields/quantity.php
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

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$max_at_a_time = $tickets_handler->get_ticket_max_purchase( $ticket->ID );
$field_label   = 'going' === $going ? __( 'Number of Guests', 'event-tickets' ) : __( 'Number of Guests Not Attending', 'event-tickets' );
?>
<div class="tribe-common-b1 tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="quantity-v2-<?php echo absint( $ticket->ID ); ?>"
	>
		<?php echo esc_html( $field_label ); ?><span class="screen-reader-text">(<?php esc_html_e( 'required', 'event-tickets' ); ?>)</span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="number"
		name="tribe_tickets[<?php echo esc_attr( absint( $ticket->ID ) ); ?>][quantity]"
		id="quantity-v2-<?php echo esc_attr( absint( $ticket->ID ) ); ?>"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-v2-form-input-number tribe-tickets__rsvp-v2-form-field-quantity"
		value="1"
		required
		min="1"
		max="<?php echo esc_attr( $max_at_a_time ); ?>"
		data-rsvp-v2-field="quantity"
	>
</div>

<?php
/**
 * RSVP V2: Email Input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/fields/email.php
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

/**
 * Set the default value for the email on the RSVP form.
 *
 * V1 backwards compatibility filter.
 *
 * @since 4.9
 *
 * @param string                           $email    The email value.
 * @param Tribe__Tickets__Editor__Template $template The template object.
 */
$email = apply_filters( 'tribe_tickets_rsvp_form_email', '', $this );

/**
 * Set the default value for the email on the RSVP V2 form.
 *
 * @since TBD
 *
 * @param string                           $email    The email value.
 * @param Tribe__Tickets__Editor__Template $template The template object.
 */
$email = apply_filters( 'tribe_tickets_rsvp_v2_form_email', $email, $this );
?>
<div class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field tribe-tickets__form-field--required">
	<label
		class="tribe-tickets__form-field-label"
		for="tribe-tickets-rsvp-v2-email-<?php echo esc_attr( $ticket->ID ); ?>"
	>
		<?php esc_html_e( 'Email', 'event-tickets' ); ?><span class="screen-reader-text"><?php esc_html_e( 'required', 'event-tickets' ); ?></span>
		<span class="tribe-required" aria-hidden="true" role="presentation">*</span>
	</label>
	<input
		type="email"
		class="tribe-common-form-control-text__input tribe-tickets__form-field-input tribe-tickets__rsvp-v2-form-field-email"
		name="tribe_tickets[<?php echo esc_attr( absint( $ticket->ID ) ); ?>][attendees][0][email]"
		id="tribe-tickets-rsvp-v2-email-<?php echo esc_attr( $ticket->ID ); ?>"
		value="<?php echo esc_attr( $email ); ?>"
		required
		placeholder="<?php esc_attr_e( 'your@email.com', 'event-tickets' ); ?>"
		data-rsvp-v2-field="email"
	>
</div>

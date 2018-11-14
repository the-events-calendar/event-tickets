<?php
/**
 * This template renders the RSVP ticket form email input
 *
 * @version 0.3.0-alpha
 *
 */
/**
 * Set the default value for the email on the RSVP form.
 *
 * @param string
 * @param Tribe__Events_Gutenberg__Template $this
 *
 * @since 0.3.0-alpha
 *
 */
$email = apply_filters( 'tribe_tickets_rsvp_form_email', '', $this );
?>
<input
	type="email"
	name="attendee[email]"
	class="tribe-tickets-email"
	placeholder="<?php esc_attr_e( 'Email', 'events-gutenberg' ); ?>"
	value="<?php echo esc_attr( $email ); ?>"
	required
/>
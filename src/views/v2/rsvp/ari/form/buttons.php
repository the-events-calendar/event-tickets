<?php
/**
 * This template renders the RSVP AR form buttons.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/buttons.php
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-form-buttons">
	<button
		class="tribe-common-h7 tribe-tickets__rsvp-form-button tribe-tickets__rsvp-form-button--cancel"
		type="reset"
	>
		<?php esc_html_e( 'Cancel', 'event-tickets' ); ?>
	</button>

	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-form-button"
		type="submit"
	>
		<?php esc_html_e( 'Finish', 'event-tickets' ); ?>
	</button>
</div>

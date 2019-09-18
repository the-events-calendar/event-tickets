<?php
/**
 * This template renders the error message for each form
 * of the attendee registration page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/error.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */
?>
<div class="tribe-tickets__item__attendee__fields__error tribe-tickets__item__attendee__fields__error--required">
	<?php esc_html_e( 'Please fill in all required fields.', 'event-tickets' ); ?>
</div>
<div class="tribe-tickets__item__attendee__fields__error tribe-tickets__item__attendee__fields__error--ajax">
	<?php esc_html_e( 'An error occurred while saving, please try again.', 'event-tickets' ); ?>
</div>

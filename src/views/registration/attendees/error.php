<?php
/**
 * This template renders the error message for each form
 * of the attendee registration page
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__item__attendee__fields__error tribe-block__tickets__item__attendee__fields__error--required">
	<?php esc_html_e( 'Please fill in all required fields', 'event-tickets' ); ?>
</div>
<div class="tribe-block__tickets__item__attendee__fields__error tribe-block__tickets__item__attendee__fields__error--ajax">
	<?php esc_html_e( 'An error occurred while saving, please try again.', 'event-tickets' ); ?>
</div>

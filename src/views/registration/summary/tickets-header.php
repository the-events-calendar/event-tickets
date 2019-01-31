<?php
/**
 * This template renders the summary tickets header
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/tickets-header.php
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__header">
	<div class="tribe-block__tickets__registration__tickets__header__summary">
		<?php esc_html_e( 'Ticket summary', 'event-tickets' ); ?>
	</div>
	<div class="tribe-block__tickets__registration__tickets__header__price">
		<?php esc_html_e( 'Price', 'event-tickets' ); ?>
	</div>
</div>

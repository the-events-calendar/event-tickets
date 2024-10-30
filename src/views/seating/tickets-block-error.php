<?php
/**
 * Seating tickets block error template.
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/tickets-block-error.php
 *
 * @since 5.16.0
 *
 * @version 5.16.0
 */

?>

<div class="tribe-common event-tickets tribe-tickets__tickets-wrapper">
	<div class="tribe-tickets__tickets-form tec-tickets-seating__tickets-block">
		<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__tickets-title">
			<?php echo esc_html( tribe_get_ticket_label_plural( 'seat-form' ) ); ?>
		</h2>
		<p>
			<?php
			echo esc_html_x(
				'Ticket sales are not available. Please contact the site administrator.',
				'Seat selection ticket block error message',
				'event-tickets'
			);
			?>
		</p>
	</div>
</div>

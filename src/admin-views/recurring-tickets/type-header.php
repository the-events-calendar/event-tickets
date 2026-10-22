<?php
/**
 * The type of a recurring event ticket, heading its form in the classic editor.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/type-header.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var int $recurring_dates How many dates the event has now.
 */

?>
<div id="ticket_type_options" class="input_block">
	<label class="ticket_form_label ticket_form_left" id="ticket_type_label" for="ticket_type">
		<?php echo esc_html_x( 'Type:', 'The label used in the ticket edit form for the type of the ticket.', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right ticket_form_right--flex">
		<img
			class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
			src="<?php echo esc_url( tribe_resource_url( 'icons/ticket-default-icon.svg', false, null, \Tribe__Tickets__Main::instance() ) ); ?>"
			alt=""
		/>
		<span class="ticket-type__text">
			<?php echo esc_html_x( 'Recurring event ticket', 'The name of the recurring event ticket type in the ticket form.', 'event-tickets' ); ?>
		</span>
	</div>
	<span class="tribe_soft_note ticket_form_right tribe-active">
		<?php
		echo esc_html(
			sprintf(
				// Translators: %d is the number of dates of the recurring event.
				_n( 'Sold for each date. %d date right now.', 'Sold for each date. %d dates right now.', $recurring_dates, 'event-tickets' ),
				$recurring_dates
			)
		);
		?>
	</span>
</div>

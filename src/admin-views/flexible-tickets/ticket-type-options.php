<?php
/**
 * The dropdown to select the ticket type.
 *
 * @since TBD
 */

$origin = \Tribe__Tickets__Main::instance();
?>

<div id="ticket_type_options" class="input_block">
	<label class="ticket_form_label ticket_form_left" id="ticket_type_label" for="ticket_type">
		<?php echo esc_html_x( 'Type:', 'The label used in the ticket edit form for the type of the ticket.', 'event-tickets' ); ?>
	</label>
	<input
			type='hidden'
			id='ticket_type'
			name='ticket_type'
			value="series_pass"
	/>
	<div class="ticket_form_right"
		 style="display: flex; align-items: center">
		<img
				class="tribe-tickets-svgicon tec-tickets-icon tec-tickets-icon__ticket-type"
				src="<?php echo esc_url( tribe_resource_url( 'icons/series-pass.svg', false, null, $origin ) ); ?>"
				alt="<?php echo esc_attr_x(
						'Series Pass',
						'The alt text for the Series Pass icon in the ticket form.',
						'event-tickets'
				); ?>"
		/>
		<span class="ticket-type__text">
		<?php echo esc_html_x(
				'Series Pass',
				'The name of the Series Pass ticket type.',
				'event-tickets'
		); ?>
		</span>
		<span class="dashicons dashicons-editor-help ticket-type__help-icon"
			  title="<?php echo esc_attr_x(
					  'A Series Pass provides an attendee with access to all events in a Series.',
					  'The help text for the Series Pass icon in the ticket form.',
					  'event-tickets'
			  ); ?>">
		</span>
	</div>
</div>

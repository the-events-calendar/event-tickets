<?php
/**
 * The ticket header for the default Ticket Type.
 *
 * @since 5.8.0
 *
 * @var string $description The help text displayed under the icon about the default ticket type.
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
			alt="<?php echo esc_attr( tribe_get_ticket_label_singular( 'admin_ticket_type_alt_text' ) ); ?>"
		/>
		<span class="ticket-type__text ticket-type__text--default">
			<?php echo esc_html( tec_tickets_get_default_ticket_type_label( 'admin_ticket_type_name' ) ); ?>
		</span>
	</div>

	<span class="tribe_soft_note ticket_form_right tribe-active">
		<?php 
		echo wp_kses(
			$description,
			[
				'a' => [
					'href'   => true,
					'target' => true,
				],
			] 
		); 
		?>
	</span>
</div>

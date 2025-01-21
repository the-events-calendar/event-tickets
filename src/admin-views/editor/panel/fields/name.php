<?php
/**
 * The template for the ticket name field.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var string $ticket_name                      The ticket name.
 * @var string $rsvp_required_type_error_message The RSVP required type error message.
 */

?>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_name">
		<?php echo esc_html_x( 'Name:', 'The ticket name label in the admin ticket edit panel.', 'event-tickets' ); ?>
	</label>
	<input
		type='text'
		id='ticket_name'
		name='ticket_name'
		class="ticket_field ticket_form_right"
		size='25'
		value="<?php echo esc_attr( $ticket_name ); ?>"
		data-validation-is-required
		data-validation-error="<?php echo esc_attr( $rsvp_required_type_error_message ); ?>"
	/>
	<span
		class="tribe_soft_note ticket_form_right"
		data-depends="#tec_tickets_ticket_provider"
		data-condition-not="Tribe__Tickets__RSVP"
	>
	<?php
		echo esc_html(
			sprintf(
			// Translators: %1$s: dynamic 'ticket' text.
				_x(
					'The %1$s name is displayed on the frontend of your website and within ticket emails.',
					'admin edit ticket panel note',
					'event-tickets'
				),
				tribe_get_ticket_label_singular_lowercase( 'admin_edit_ticket_panel_note' )
			)
		);
		?>
	</span>
	<span
		class="tribe_soft_note ticket_form_right"
		data-depends="#tec_tickets_ticket_provider"
		data-condition="Tribe__Tickets__RSVP"
	>
	<?php
		echo esc_html(
			sprintf(
			// Translators: %1$s: dynamic 'RSVP' text.
				_x(
					'The %1$s name is displayed on the frontend of your website and within %1$s emails.',
					'admin edit RSVP panel note',
					'event-tickets'
				),
				tribe_get_rsvp_label_singular( 'admin_edit_rsvp_panel_note' )
			)
		);
		?>
	</span>
</div>

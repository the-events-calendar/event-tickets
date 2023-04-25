<?php
/**
 * The dropdown to select the ticket type.
 *
 * @since TBD
 *
 * @var string                        $ticket_type The type of the ticket.
 * @var TEC\Tickets\Admin\Editor_Data $editor_data The data to be used in the editor.
 */

?>

<div id="ticket_type_options">
	<label class="ticket_form_label ticket_form_left" id="ticket_type_label" for="ticket_type">
		<?php echo esc_html( $editor_data->get_raw_data_entry( 'ticket_type_label_default' ) ); ?>
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
				src="<?php echo esc_url( $editor_data->get_raw_data_entry( 'ticket_type_icon_url_' . $ticket_type ) ); ?>"
				alt="<?php echo esc_attr( $editor_data->get_raw_data_entry( 'ticket_type_icon_alt_text_' . $ticket_type ) ); ?>"
		/>
		<span class="ticket-type__text">
		<?php echo esc_html( $editor_data->get_raw_data_entry( 'ticket_type_name_' . $ticket_type ) ); ?>
	</span>
		<span class="dashicons dashicons-editor-help ticket-type__help-icon"
			  title="<?php echo esc_attr( $editor_data->get_raw_data_entry( 'ticket_type_help_' . $ticket_type ) ); ?>">
	</div>
</div>

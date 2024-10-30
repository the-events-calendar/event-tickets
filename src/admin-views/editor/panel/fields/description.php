<?php
/**
 * The description field for the ticket editor.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 *
 * @var string $ticket_description The ticket description.
 * @var array<string, mixed> $ticket The ticket.
 */

?>

<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_description">
		<?php esc_html_e( 'Description:', 'event-tickets' ); ?>
	</label>
	<textarea
		rows="5"
		cols="40"
		name="ticket_description"
		class="ticket_field ticket_form_right"
		id="ticket_description"
	><?php echo esc_textarea( $ticket_description ); ?></textarea>
	<div class="input_block">
		<label class="tribe_soft_note">
			<input
				type="checkbox"
				id="tribe_tickets_show_description"
				name="ticket_show_description"
				value="1"
				class="ticket_field ticket_form_left"
				<?php checked( true, $ticket ? $ticket->show_description : true ); ?>
			>
			<?php
			echo esc_html(
				sprintf(
				// Translators: %s: dynamic 'tickets' text.
					_x(
						'Show description on frontend %s form.',
						'default ticket provider',
						'event-tickets'
					),
					tribe_get_ticket_label_singular_lowercase( 'default_ticket_provider' )
				)
			);
			?>
		</label>
	</div>
</div>

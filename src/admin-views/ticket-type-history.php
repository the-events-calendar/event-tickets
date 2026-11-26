<?php
/**
 * @var Tribe__Post_History $history
 * @var string $provider
 */
$entry_list = $history->get_entries();
	if ( ! empty( $entry_list ) ) : ?>
	<div class="input_block ticket_advanced ticket_advanced_<?php echo esc_attr( $provider ); ?> history">

		<label for="ticket_history" class="ticket_form_label ticket_form_left"> <?php
			echo esc_html(
				sprintf(
					// translators: %s is the singular ticket label, "Ticket" by default.
					_x( '%s History:', 'ticket type history', 'event-tickets' ),
					tribe_get_ticket_label_singular( 'ticket_type_history' )
				)
			); ?>
		</label>

		<div class="ticket_form_right">
			<ul>
				<?php foreach ( $entry_list as $entry ) : ?>
					<li>
						<span class="date"><?php echo esc_html( $entry->datetime ); ?> </span>
						<span class="details"><?php echo wp_kses_post( $entry->message ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif;

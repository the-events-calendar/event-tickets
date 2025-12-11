<?php
/**
 * RSVP V2: Opt-in Toggle
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/success/toggle.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket                The RSVP ticket object.
 * @var int                           $post_id               The event post ID.
 * @var bool                          $opt_in_toggle_hidden  Whether opt-in toggle is hidden.
 * @var string                        $opt_in_attendee_ids   The list of attendee IDs to send.
 * @var string                        $opt_in_nonce          The nonce for opt-in AJAX requests.
 * @var bool                          $opt_in_checked        Whether the opt-in field should be checked.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

if ( $opt_in_toggle_hidden ) {
	return;
}

$toggle_id = 'toggle-rsvp-v2-' . $ticket->ID;
?>

<div class="tribe-tickets__rsvp-v2-actions-success-going-toggle tribe-common-form-control-toggle">
	<input
		class="tribe-common-form-control-toggle__input tribe-tickets__rsvp-v2-actions-success-going-toggle-input"
		id="<?php echo esc_attr( $toggle_id ); ?>"
		name="toggleGroup"
		type="checkbox"
		value="toggleOne"
		<?php checked( $opt_in_checked ); ?>
		data-rsvp-v2-id="<?php echo esc_attr( $ticket->ID ); ?>"
		data-rsvp-v2-attendee-ids="<?php echo esc_attr( $opt_in_attendee_ids ); ?>"
		data-rsvp-v2-opt-in-nonce="<?php echo esc_attr( $opt_in_nonce ); ?>"
	/>
	<label
		class="tribe-common-form-control-toggle__label tribe-tickets__rsvp-v2-actions-success-going-toggle-label"
		for="<?php echo esc_attr( $toggle_id ); ?>"
	>
		<span
			data-js="tribe-tickets-tooltip"
			data-tooltip-content="#tribe-tickets-tooltip-content-v2-<?php echo esc_attr( $ticket->ID ); ?>"
			aria-describedby="tribe-tickets-tooltip-content-v2-<?php echo esc_attr( $ticket->ID ); ?>"
		>
			<?php
			echo wp_kses_post(
				sprintf(
					// Translators: 1: opening span. 2: Closing span.
					_x(
						'Show me on public %1$sattendee list%2$s',
						'Toggle for RSVP attendee list.',
						'event-tickets'
					),
					'<span class="tribe-tickets__rsvp-v2-actions-success-going-toggle-label-underline">',
					'</span>'
				)
			);
			?>
		</span>
	</label>
	<?php $this->template( 'v2/rsvp-v2/actions/success/tooltip', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
</div>

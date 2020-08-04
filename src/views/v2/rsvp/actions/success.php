<?php
/**
 * Block: RSVP
 * Actions - Success
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/success.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since 4.12.3
 * @version 4.12.3
 */

$toggle_id = 'toggle-rsvp-' . $rsvp->ID;
$attendee_ids = '';
$opt_in_nonce = '';

if ( ! empty( $process_result['opt_in_args'] ) ) {
	$attendee_ids = $process_result['opt_in_args']['attendee_ids'];
	$opt_in_nonce = $process_result['opt_in_args']['opt_in_nonce'];
}
?>
<div class="tribe-tickets__rsvp-actions-success">

	<?php $this->template( 'v2/rsvp/actions/success/title' ); ?>

	<div class="tribe-tickets__rsvp-actions-success-going-toggle tribe-common-form-control-toggle">
		<input
			class="tribe-common-form-control-toggle__input tribe-tickets__rsvp-actions-success-going-toggle-input"
			id="<?php echo esc_attr( $toggle_id ); ?>"
			name="toggleGroup"
			type="checkbox"
			value="toggleOne"
			data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>"
			data-attendee-ids="<?php echo esc_attr( $attendee_ids ); ?>"
			data-opt-in-nonce="<?php echo esc_attr( $opt_in_nonce ); ?>"
		/>
		<label
			class="tribe-common-form-control-toggle__label tribe-tickets__rsvp-actions-success-going-toggle-label"
			for="<?php echo esc_attr( $toggle_id ); ?>"
		>
			<span
				data-js="tribe-tickets-tooltip"
				data-tooltip-content="#tribe-tickets-tooltip-content-<?php echo esc_attr( $rsvp->ID ); ?>"
				aria-describedby="tribe-tickets-tooltip-content-<?php echo esc_attr( $rsvp->ID ); ?>"
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
						'<span class="tribe-tickets__rsvp-actions-success-going-toggle-label-underline">',
						'</span>'
					)
				);
				?>
			</span>
		</label>
		<?php $this->template( 'v2/rsvp/actions/success/tooltip', [ 'rsvp' => $rsvp ] ); ?>
	</div>
</div>

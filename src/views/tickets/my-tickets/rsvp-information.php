<?php
/**
 * My Tickets: RSVP Information
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/rsvp-information.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Tickets $provider The ticket provider.
 * @var array                   $attendee The attendee data.
 * @var int                     $order_id The ID of the order.
 */

use TEC\Tickets\Commerce\RSVP\Constants;

// Check if this is a TC-RSVP ticket and if "Can't Go" is enabled.
$cant_go_enabled     = false;
$ticket              = null;
$can_change_to_going = true;

if ( ! empty( $attendee['product_id'] ) ) {
	$ticket = Tribe__Tickets__Tickets::load_ticket_object( $attendee['product_id'] );
	if ( $ticket && Constants::TC_RSVP_TYPE === $ticket->type() ) {
		$cant_go_enabled = tribe_is_truthy( get_post_meta( $ticket->ID, '_tribe_ticket_show_not_going', true ) );

		// Check if user can change from "Not Going" to "Going" based on capacity.
		$current_status = $attendee['rsvp_status'] ?? 'yes';
		if ( $current_status === 'no' ) {
			$remaining_capacity = $ticket->available();
			// -1 means unlimited capacity.
			$can_change_to_going = $remaining_capacity === - 1 || $remaining_capacity > 0;
		}
	}
}

$price = '';
if ( ! empty( $provider ) ) {
	$price = $provider->get_price_html( $attendee['product_id'], $attendee );
}
?>
<div class="tribe-ticket-information">
	<?php if ( $cant_go_enabled ) : ?>
		<?php
		$current_status  = $attendee['rsvp_status'] ?? 'yes';
		$going_label     = esc_html__( 'Going', 'event-tickets' );
		$not_going_label = esc_html__( 'Not Going', 'event-tickets' );
		?>
		<div class="tribe-rsvp-status-change">
			<label for="rsvp-status-<?php echo esc_attr( $attendee['attendee_id'] ); ?>">
				<?php esc_html_e( 'Response:', 'event-tickets' ); ?>
			</label>
			<select
				name="attendee[<?php echo esc_attr( $order_id ); ?>][rsvp_status][<?php echo esc_attr( $attendee['attendee_id'] ); ?>]"
				id="rsvp-status-<?php echo esc_attr( $attendee['attendee_id'] ); ?>"
				class="tribe-rsvp-status-select"
			>
				<option value="yes" <?php selected( $current_status, 'yes' ); ?> <?php disabled( $current_status === 'no' && ! $can_change_to_going ); ?>>
					<?php echo esc_html( $going_label ); ?>
				</option>
				<option value="no" <?php selected( $current_status, 'no' ); ?>>
					<?php echo esc_html( $not_going_label ); ?>
				</option>
			</select>
			<?php if ( $current_status === 'no' && ! $can_change_to_going ) : ?>
				<span class="tribe-rsvp-capacity-notice">
					<?php esc_html_e( 'Event is at capacity', 'event-tickets' ); ?>
				</span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

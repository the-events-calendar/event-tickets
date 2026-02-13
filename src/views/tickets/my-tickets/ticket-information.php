<?php
/**
 * My Tickets: Ticket Information
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/ticket-information.php
 *
 * @since 5.6.7
 *
 * @since 5.9.1 Corrected template override filepath
 *
 * @version 5.9.1
 *
 * @var Tribe__Tickets__Tickets $provider The ticket provider.
 * @var array                   $attendee The attendee data.
 */

use TEC\Tickets\RSVP\V2\Constants;

defined( 'ABSPATH' ) || exit;

?>
<div class="tribe-ticket-information">
	<?php
	$price = '';
	if ( ! empty( $provider ) ) {
		$price = $provider->get_price_html( $attendee['product_id'], $attendee );
	}
	?>
	<?php if ( ! empty( $attendee['ticket_exists'] ) ) : ?>
		<span class="ticket-name"><?php echo esc_html( $attendee['ticket'] ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $attendee['ticket_type'] ) && Constants::TC_RSVP_TYPE === $attendee['ticket_type'] ) : ?>
		<?php
		$ticket_id         = (int) ( $attendee['product_id'] ?? 0 );
		$attendee_is_going = metadata_exists( 'post', $attendee['ID'], Constants::RSVP_STATUS_META_KEY ) ? tribe_is_truthy( get_post_meta( $attendee['ID'], Constants::RSVP_STATUS_META_KEY, true ) ) : true;
		$show_not_going    = false;
		if ( $ticket_id ) {
			$show_not_going = tribe_is_truthy( get_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, true ) );
		}

		if ( $show_not_going ) {
			?>
			<span class="ticket-status">
				<?php esc_html_e( 'Response:', 'event-tickets' ); ?>
				<select name="attendee[<?php echo esc_attr( $attendee['ID'] ); ?>][order_status]" class="ticket-status-select">
					<option value="going" <?php selected( $attendee_is_going, true ); ?>><?php esc_html_e( 'Going', 'event-tickets' ); ?></option>
					<option value="not_going" <?php selected( $attendee_is_going, false ); ?>><?php esc_html_e( 'Not going', 'event-tickets' ); ?></option>
				</select>
			</span>
			<?php
		} else {
			?>
			<span class="ticket-status">
				<?php esc_html_e( 'Response:', 'event-tickets' ); ?>
				<span class="ticket-status-value"><?php echo $attendee_is_going ? esc_html__( 'Going', 'event-tickets' ) : esc_html__( 'Not going', 'event-tickets' ); ?></span>
			</span>
			<?php
		}
		?>
	<?php endif; ?>
	<?php if ( ! empty( $price ) ): ?>
		- <span class="ticket-price"><?php echo $price; ?></span>
	<?php endif; ?>
</div>

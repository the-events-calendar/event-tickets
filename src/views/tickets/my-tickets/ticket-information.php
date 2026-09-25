<?php
/**
 * My Tickets: Ticket Information
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/ticket-information.php
 *
 * @since 5.6.7
 *
 * @since 5.9.1 Corrected template override filepath
 * @since TBD Shows the name the ticket was bought under when the ticket no longer exists.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Tickets $provider The ticket provider.
 * @var array                   $attendee The attendee data.
 */

use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;

?>
<div class="tribe-ticket-information">
	<?php
	$price = '';
	if ( ! empty( $provider ) ) {
		$price = $provider->get_price_html( $attendee['product_id'], $attendee );
	}
	?>
	<?php $ticket_name = ! empty( $attendee['ticket_exists'] ) ? $attendee['ticket'] : ( $attendee['deleted_ticket_name'] ?? '' ); ?>
	<?php if ( '' !== (string) $ticket_name && RSVP_V2_Constants::TC_RSVP_TYPE !== ( $attendee['ticket_type'] ?? '' ) ) : ?>
		<span class="ticket-name"><?php echo esc_html( $ticket_name ); ?></span>
	<?php endif; ?>
	<?php
	/**
	 * Fires after the ticket name in the My Tickets ticket information template.
	 *
	 * @since 5.30.0
	 *
	 * @param array<string,mixed> $attendee The attendee data.
	 */
	do_action( 'tec_tickets_my_tickets_ticket_information_after_ticket_name', $attendee );
	?>
	<?php if ( ! empty( $price ) ) : ?>
		- <span class="ticket-price"><?php echo wp_kses_post( $price ); ?></span>
	<?php endif; ?>
</div>

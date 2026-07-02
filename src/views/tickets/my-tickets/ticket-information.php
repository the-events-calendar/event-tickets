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
	<?php
	/**
	 * Fires after the ticket name in the My Tickets ticket information template.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attendee The attendee data.
	 */
	do_action( 'tec_tickets_my_tickets_ticket_information_after_ticket_name', $attendee );
	?>
	<?php if ( ! empty( $price ) ) : ?>
		- <span class="ticket-price"><?php echo wp_kses_post( $price ); ?></span>
	<?php endif; ?>
</div>

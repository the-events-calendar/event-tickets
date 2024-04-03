<?php
/**
 * My Tickets: Ticket Information
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe/tickets/tickets/my-tickets/ticket-information.php
 *
 * @since 5.6.7
 *
 * @since TBD Corrected template override filepath
 *
 * @version TBD
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
	<?php if ( ! empty( $price ) ): ?>
		- <span class="ticket-price"><?php echo $price; ?></span>
	<?php endif; ?>
</div>
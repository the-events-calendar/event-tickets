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

?>
<div class="tribe-ticket-information">
	<?php
	$price = '';
	if ( ! empty( $provider ) ) {
		$price = $provider->get_price_html( $attendee['product_id'], $attendee );
	}
	?>
	<?php $ticket_name = ! empty( $attendee['ticket_exists'] ) ? $attendee['ticket'] : ( $attendee['deleted_ticket_name'] ?? '' ); ?>
	<?php if ( '' !== (string) $ticket_name ) : ?>
		<span class="ticket-name"><?php echo esc_html( $ticket_name ); ?></span>
	<?php endif; ?>
	<?php if ( ! empty( $price ) ): ?>
		- <span class="ticket-price"><?php echo $price; ?></span>
	<?php endif; ?>
</div>
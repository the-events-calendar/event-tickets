<?php
/**
 * Block: RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$event_id = $this->get( 'post_id' );
$tickets  = $this->get( 'tickets' );

// We don't display anything if there is no RSVP
if ( empty( count( $tickets ) ) ) {
	return false;
}

// Get active RSVPs
$active_tickets = array();

foreach ( $tickets as $ticket ) {
	if ( tribe_events_ticket_is_on_sale( $ticket ) ) {
		$active_tickets[] = $ticket;
	}
}

$has_active_tickets = ! empty( count( $active_tickets ) );

if ( ! $has_active_tickets ) {
	$active_past = true;
	$timestamp   = current_time( 'timestamp' );

	foreach ( $tickets as $ticket ) {
		$active_past = ( $active_past && $ticket->date_is_later( $timestamp ) );
	}
}
?>

<?php $this->template( 'blocks/attendees/order-links', array( 'type' => 'RSVP' ) ); ?>

<div class="tribe-block tribe-block__rsvp">
	<?php if ( $has_active_tickets ) : ?>
		<?php foreach ( $active_tickets as $ticket ) : ?>
			<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $ticket->ID ); ?>">
				<?php $this->template( 'blocks/rsvp/icon' ); ?>
				<?php $this->template( 'blocks/rsvp/content', array( 'ticket' => $ticket ) ); ?>
				<?php $this->template( 'blocks/rsvp/loader' ); ?>
			</div>
		<?php endforeach; ?>
	<?php else : ?>
		<div class="tribe-block__rsvp__ticket tribe-block__rsvp__ticket--inactive">
			<?php $this->template( 'blocks/rsvp/icon' ); ?>
			<?php $this->template( 'blocks/rsvp/content-inactive', array( 'active_past' => $active_past ) ); ?>
		</div>
	<?php endif; ?>
</div>

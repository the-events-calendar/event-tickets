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

$event_id  = $this->get( 'post_id' );
$items     = $this->get( 'rsvp' );
$num_items = count( $items );

// We don't display anything if there is no RSVP
if ( empty( $num_items ) ) {
	return false;
}

// Get active RSVPs
$active_items = array();

foreach ( $items as $item ) {
	if ( tribe_events_ticket_is_on_sale( $item ) ) {
		$active_items[] = $item;
	}
}

$num_active_items = count( $active_items );
$has_active_items = ! empty( $num_active_items );

if ( ! $has_active_items ) {
	$active_past = true;
	$timestamp   = current_time( 'timestamp' );

	foreach ( $items as $item ) {
		$active_past = ( $active_past && $item->date_is_later( $timestamp ) );
	}
}
?>

<?php $this->template( 'blocks/attendees/order-links', array( 'type' => 'RSVP' ) ); ?>

<div class="tribe-block tribe-block__rsvp">
	<?php if ( $has_active_items ) : ?>
		<?php foreach ( $active_items as $item ) : ?>
			<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $item->ID ); ?>">
				<?php $this->template( 'blocks/rsvp/icon' ); ?>
				<?php $this->template( 'blocks/rsvp/content', array( 'ticket' => $item ) ); ?>
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

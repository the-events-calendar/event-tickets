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
$items = $this->get( 'rsvp' );

?>

<?php $this->template( 'blocks/attendees/order-links', array( 'type' => 'RSVP' ) ); ?>

<div class="tribe-block tribe-block__rsvp">

	<?php foreach ( $items as $item ) : ?>

		<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $item->ID ); ?>" id="tribe-block__rsvp__ticket-<?php echo absint( $item->ID ); ?>">

			<?php $this->template( 'blocks/rsvp/icon' ); ?>

			<?php $this->template( 'blocks/rsvp/content', array( 'ticket' => $item ) ); ?>

			<?php $this->template( 'blocks/rsvp/loader' ); ?>

		</div>

	<?php endforeach; ?>

</div>

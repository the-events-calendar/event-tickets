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

?>

<?php $this->template( 'blocks/attendees/order-links', array( 'type' => 'RSVP' ) ); ?>

<div class="tribe-block tribe-block__rsvp">

	<?php foreach ( $tickets as $ticket ) : ?>

		<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $ticket->ID ); ?>">

			<?php $this->template( 'blocks/rsvp/icon' ); ?>

			<?php $this->template( 'blocks/rsvp/content', array( 'ticket' => $ticket ) ); ?>

			<?php $this->template( 'blocks/rsvp/loader' ); ?>

		</div>

	<?php endforeach; ?>

</div>
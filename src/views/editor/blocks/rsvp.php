<?php
/**
 * This template renders the RSVP ticket form
 *
 * @version 0.3.0-alpha
 *
 */

$event_id = $this->get( 'post_id' );
$tickets  = $this->get( 'tickets' );

?>

<?php $this->template( 'editor/blocks/attendees/order-links', array( 'type' => 'RSVP' ) ); ?>

<div class="tribe-block tribe-block__rsvp">

	<?php foreach ( $tickets as $ticket ) : ?>

		<div class="tribe-block__rsvp__ticket" data-rsvp-id="<?php echo absint( $ticket->ID ); ?>">

			<?php $this->template( 'editor/blocks/rsvp/icon' ); ?>

			<?php $this->template( 'editor/blocks/rsvp/content', array( 'ticket' => $ticket ) ); ?>

			<?php $this->template( 'editor/blocks/rsvp/loader' ); ?>

		</div>

	<?php endforeach; ?>

</div>
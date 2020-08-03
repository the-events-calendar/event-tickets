<?php
/**
 * Block: RSVP
 * Details Availability
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since 4.12.3
 *
 * @version 4.12.3
 */

$is_unlimited = -1 === $rsvp->remaining();

?>
<div class="tribe-tickets__rsvp-availability tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
	<?php if ( ! $rsvp->is_in_stock() ) : ?>
		<?php $this->template( 'v2/rsvp/details/availability/full', [ 'rsvp' => $rsvp ] ); ?>
	<?php elseif ( $is_unlimited ) : ?>
		<?php $this->template( 'v2/rsvp/details/availability/unlimited', [ 'is_unlimited' => $is_unlimited ] ); ?>
	<?php else : ?>
		<?php $this->template( 'v2/rsvp/details/availability/remaining', [ 'rsvp' => $rsvp ] ); ?>
	<?php endif; ?>

	<?php $this->template( 'v2/rsvp/details/availability/days-to-rsvp', [ 'rsvp' => $rsvp ] ); ?>

</div>

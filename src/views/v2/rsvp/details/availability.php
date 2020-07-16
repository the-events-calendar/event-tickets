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
 * @since TBD
 *
 * @version TBD
 */

$is_unlimited     = - 1 === $rsvp->remaining();
$is_in_stock      = $rsvp->is_in_stock();
$has_days_to_rsvp = false;

if ( $is_in_stock ) {
	$days_to_rsvp     = $this->template( 'v2/rsvp/details/availability/days-to-rsvp', [ 'rsvp' => $rsvp ], false );
	$has_days_to_rsvp = ! empty( $days_to_rsvp );
}
?>
<div class="tribe-tickets__rsvp-availability tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
	<?php if ( ! $is_in_stock ) : ?>
		<?php $this->template( 'v2/rsvp/details/availability/full', [ 'rsvp' => $rsvp ] ); ?>
	<?php elseif ( $is_unlimited ) : ?>
		<?php $this->template( 'v2/rsvp/details/availability/unlimited', [ 'is_unlimited' => $is_unlimited, 'has_days_to_rsvp' => $has_days_to_rsvp ] ); ?>
	<?php else : ?>
		<?php $this->template( 'v2/rsvp/details/availability/remaining', [ 'rsvp' => $rsvp, 'has_days_to_rsvp' => $has_days_to_rsvp ] ); ?>
	<?php endif; ?>

	<?php echo $days_to_rsvp; ?>
</div>

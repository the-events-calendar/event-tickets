<?php
/**
 * RSVP V2: Availability Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/availability/availability.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
 * @var int                           $post_id The event post ID.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

use Tribe__Date_Utils as Dates;

$is_unlimited = -1 === $ticket->remaining();
$is_in_stock  = $ticket->is_in_stock();

$days_to_rsvp = Dates::date_diff( current_time( 'mysql' ), $ticket->end_date );
$days_to_rsvp = floor( $days_to_rsvp );

// Only show Days to RSVP if it is happening within the next week and is in stock.
if ( ! $is_in_stock || 6 < $days_to_rsvp ) {
	$days_to_rsvp = false;
}
?>
<div class="tribe-tickets__rsvp-v2-availability tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
	<?php if ( ! $is_in_stock ) : ?>
		<?php $this->template( 'v2/rsvp-v2/details/availability/full', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
	<?php elseif ( $is_unlimited ) : ?>
		<?php
		$this->template(
			'v2/rsvp-v2/details/availability/unlimited',
			[
				'ticket'       => $ticket,
				'post_id'      => $post_id,
				'is_unlimited' => $is_unlimited,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php else : ?>
		<?php
		$this->template(
			'v2/rsvp-v2/details/availability/remaining',
			[
				'ticket'       => $ticket,
				'post_id'      => $post_id,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php endif; ?>

	<?php if ( false !== $days_to_rsvp ) : ?>
		<?php
		$this->template(
			'v2/rsvp-v2/details/availability/days-to-rsvp',
			[
				'ticket'       => $ticket,
				'post_id'      => $post_id,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php endif; ?>
</div>

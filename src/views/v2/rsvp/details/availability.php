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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.12.3
 *
 * @version 4.12.3
 *
 * @var Tribe__Tickets__Editor__Template $this                Template object.
 * @var int                              $post_id             [Global] The current Post ID to which RSVPs are attached.
 * @var array                            $attributes          [Global] RSVP attributes (could be empty).
 * @var Tribe__Tickets__Ticket_Object[]  $active_rsvps        [Global] List of RSVPs.
 * @var bool                             $all_past            [Global] True if RSVPs availability dates are all in the past.
 * @var bool                             $has_rsvps           [Global] True if the event has any RSVPs.
 * @var bool                             $has_active_rsvps    [Global] True if the event has any RSVPs available.
 * @var bool                             $must_login          [Global] True if only logged-in users may obtain RSVPs.
 * @var string                           $login_url           [Global] The site's login URL.
 * @var int                              $threshold           [Global] The count at which "number of tickets left" message appears.
 * @var null|string                      $step                [Global] The point we're at in the loading process.
 * @var bool                             $opt_in_checked      [Global] Whether appearing in Attendee List was checked.
 * @var string                           $opt_in_attendee_ids [Global] The list of attendee IDs to send in the form submission.
 * @var string                           $opt_in_nonce        [Global] The nonce for opt-in AJAX requests.
 * @var bool                             $doing_shortcode     [Global] True if detected within context of shortcode output.
 * @var bool                             $block_html_id       [Global] The RSVP block HTML ID. $doing_shortcode may alter it.
 * @var Tribe__Tickets__Ticket_Object    $rsvp                The rsvp ticket object.
 */

use Tribe__Date_Utils as Dates;

$is_unlimited = - 1 === $rsvp->remaining();
$is_in_stock  = $rsvp->is_in_stock();

$days_to_rsvp = Dates::date_diff( current_time( 'mysql' ), $rsvp->end_date );
$days_to_rsvp = floor( $days_to_rsvp );

// Only show Days to RSVP if it is happening within the next week and is in stock.
if ( ! $is_in_stock || 6 < $days_to_rsvp ) {
	$days_to_rsvp = false;
}
?>
<div class="tribe-tickets__rsvp-availability tribe-common-h6 tribe-common-h--alt tribe-common-b3--min-medium">
	<?php if ( ! $is_in_stock ) : ?>
		<?php $this->template( 'v2/rsvp/details/availability/full', [ 'rsvp' => $rsvp ] ); ?>
	<?php elseif ( $is_unlimited ) : ?>
		<?php
		$this->template(
			'v2/rsvp/details/availability/unlimited',
			[
				'is_unlimited' => $is_unlimited,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php else : ?>
		<?php
		$this->template(
			'v2/rsvp/details/availability/remaining',
			[
				'rsvp'         => $rsvp,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php endif; ?>

	<?php if ( false !== $days_to_rsvp ) : ?>
		<?php
		$this->template(
			'v2/rsvp/details/availability/days-to-rsvp',
			[
				'rsvp'         => $rsvp,
				'days_to_rsvp' => $days_to_rsvp,
			]
		);
		?>
	<?php endif; ?>
</div>

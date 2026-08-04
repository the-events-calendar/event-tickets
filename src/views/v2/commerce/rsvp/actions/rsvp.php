<?php
/**
 * Block: RSVP
 * Actions - RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since 4.12.3
 * @version 4.12.3
 */

defined( 'ABSPATH' ) || die();
?>
<div class="tribe-tickets__rsvp-actions-rsvp">
	<?php $this->template( 'v2/commerce/rsvp/actions/rsvp/going', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/commerce/rsvp/actions/rsvp/not-going', [ 'rsvp' => $rsvp ] ); ?>
</div>

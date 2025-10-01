<?php
/**
 * Block: RSVP
 * Details Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/details/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
* @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 * @version TBD
 */

?>
<h3 class="tribe-tickets__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
	<?php echo wp_kses_post( $rsvp->name ); ?>
</h3>

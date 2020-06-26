<?php
/**
 * Block: RSVP
 * Details Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 * @version TBD
 */

if ( ! $rsvp->show_description() ) {
	return;
}
?>
<div class="tribe-tickets__rsvp-description tribe-common-b1 tribe-common-b3--min-medium">
	<?php echo wpautop( wp_kses_post( $rsvp->description ) ); ?>
</div>

<?php
/**
 * Block: RSVP
 * Details Availability - Days to RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability/days-to-rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

// Bail if RSVP isn't in stock.
if ( ! $rsvp->is_in_stock() ) {
	return;
}

use Tribe__Date_Utils as Dates;
$days_to_rsvp = Dates::date_diff( $rsvp->start_date, $rsvp->end_date );
?>
<span class="tribe-tickets__rsvp-availability-days-left tribe-common-b2--bold"><?php echo esc_html( $days_to_rsvp ); ?> </span>
<?php
echo esc_html(
	sprintf(
		/* Translators: 1: RSVP label. */
		_x( 'days left to %1$s', 'blocks rsvp days left', 'event-tickets' ),
		tribe_get_rsvp_label_singular( 'blocks_rsvp_messages_success' )
	)
);
?>

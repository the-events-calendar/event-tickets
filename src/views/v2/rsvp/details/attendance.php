<?php
/**
 * Block: RSVP
 * Details Attendance
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/attendance.php
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

$classes = [ 'tribe-tickets__rsvp-attendance-number' ];
if ( ! $rsvp->show_description() || empty( $rsvp->description ) ) {
	$classes[] = 'tribe-common-h1';
} else {
	$classes[] = 'tribe-common-h4';
}
?>
<div class="tribe-tickets__rsvp-attendance">
	<span <?php tribe_classes( $classes ); ?>>
		<?php echo esc_html( $rsvp->qty_sold ); ?>
	</span>
	<span class="tribe-tickets__rsvp-attendance-going tribe-common-b3">
		<?php esc_html_e( 'Going', 'event-tickets' ); ?>
	</span>
</div>

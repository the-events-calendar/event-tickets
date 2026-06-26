<?php
/**
 * Block: RSVP
 * Attendees - Attendee Name
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees/attendee/name.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 * @var array $attendees List of attendees for the given order.
 * @var int $attendee_id The attendee ID.
 *
 * @since 5.7.1
 * @since TBD Read the attendee name from the Tickets Commerce meta key used by RSVP V2, with fallbacks.
 *
 * @version TBD
 */

use TEC\Tickets\Commerce\Attendee;

defined( 'ABSPATH' ) || die();
if ( empty( $attendees ) || empty( $attendee_id ) ) {
	return;
}

$attendee_name = get_post_meta( $attendee_id, tribe( Tribe__Tickets__RSVP::class )->full_name, true );

// RSVP V2 creates Tickets Commerce attendees, which store the holder name under a different meta key.
if ( empty( $attendee_name ) ) {
	$attendee_name = get_post_meta( $attendee_id, Attendee::$full_name_meta_key, true );
}

// Final fallback to the attendee post title, which is also set to the holder name on creation.
if ( empty( $attendee_name ) ) {
	$attendee_name = get_the_title( $attendee_id );
}

if ( empty( $attendee_name ) ) {
	return;
}

?>
<div class="tec-tickets__attendees-list-item-attendee-details-name tribe-common-b1--bold">
	<?php echo esc_html( $attendee_name ); ?>
</div>

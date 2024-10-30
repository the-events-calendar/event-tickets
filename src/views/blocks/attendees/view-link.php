<?php
/**
 * Block: Attendees List
 *
 * Link to Tickets
 * Included on the Events Single Page after the meta
 * the Message that Will link to the Tickets Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/attendees/view-link.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 4.9
 * @since 4.10.8 Renamed template from order-links.php to view-link.php. Updated to not use the now-deprecated
 *               third parameter of `get_description_rsvp_ticket()` and to simplify the template's logic.
 * @since 4.10.9 Uses new functions to get singular and plural texts.
 * @since 4.12.1 Account for empty post type object, such as if post type got disabled. Fix typo in sprintf placeholders.
 * @since 5.0.2 Fix template path in documentation block.
 * @since 5.3.2 Added use of $hide_view_my_tickets_link variable to hide link as an option.
 * @since 5.8.0 Simplified the template's logic and updated link label.
 *
 * @version 5.8.0
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( isset( $hide_view_my_tickets_link ) && tribe_is_truthy( $hide_view_my_tickets_link ) ) {
	return;
}

$view     = Tribe__Tickets__Tickets_View::instance();
$event_id = $this->get( 'post_id' ) ?? get_the_ID();

$data = $view->get_my_tickets_link_data( $event_id, get_current_user_id() );

if ( empty( $data['total_count'] ) ) {
	return;
}

?>
<div class="tribe-link-view-attendee">
	<?php echo esc_html( $data['message'] ); ?>
	<a href="<?php echo esc_url( $data['link'] ); ?>">
		<?php echo esc_html( $data['link_label'] ); ?>
	</a>
</div>

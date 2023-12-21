<?php
/**
 * Generic: Success Order Page Attendee list - Attendee name template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/components/attendees-list/attendees/attendee/name.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.7.1
 *
 * @version 5.7.1
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array            $attendees             [Global] List of attendees for the given order.
 * @var array            $attendee              The current attendee.
 */

if ( empty( $attendee ) || empty( $attendee['holder_name'] ) ) {
	return;
}

?>
<div class="tec-tickets__attendees-list-item-attendee-details-name tribe-common-b1--bold">
	<?php echo esc_html( $attendee['holder_name'] ); ?>
</div>

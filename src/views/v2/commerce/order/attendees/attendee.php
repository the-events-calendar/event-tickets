<?php
/**
 * Tickets Commerce: Success Order Page Attendee list - Attendee template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/attendees/attendee.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
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

if ( empty( $order ) || empty( $attendee ) ) {
	return;
}

use TEC\Tickets\Commerce\Utils\Value;
$total = Value::create( $attendee['price_paid'] );
?>
<div class="tribe-tickets__commerce-order-attendees-list-attendee-details">
	<?php echo esc_html( $attendee['ticket'] ) . ' - ' . esc_html( $total->get_currency() ); ?>
</div>

<?php
/**
 * Tickets Commerce: Success Order Page Attendee list title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/attendees/title.php
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
 */

if ( empty( $order ) || empty( $attendees ) ) {
	return;
}
?>
<h4 class="tribe-common-h4 tribe-common-h--alt">
	<?php
	echo sprintf(
		esc_html__( 'Your %s', 'event-tickets' ),
		tribe_get_ticket_label_plural( 'tickets_commerce_success_page_your_tickets' )
	); ?>
</h4>

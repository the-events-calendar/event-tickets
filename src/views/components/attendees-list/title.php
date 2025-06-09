<?php
/**
 * Generic: Success Order Page Attendee list - title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/components/attendees-list/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.7.0
 *
 * @version 5.7.0
 *
 * @var \Tribe__Template $this          [Global] Template object.
 * @var Module           $provider      [Global] The tickets provider instance.
 * @var string           $provider_id   [Global] The tickets provider class name.
 * @var \WP_Post         $order         [Global] The order object.
 * @var int              $order_id      [Global] The order ID.
 * @var bool             $is_tec_active [Global] Whether `The Events Calendar` is active or not.
 * @var array            $attendees     [Global] List of attendees for the given order.
 */

if ( empty( $order ) || empty( $attendees ) ) {
	return;
}
?>
<h4 class="tribe-common-h4 tribe-common-h--alt">
	<?php
	// Make the label_plural dynamic based on provider name
	echo sprintf(
	// Translators: %s is the plural label for tickets.
		esc_html__( 'Your %s', 'event-tickets' ),
		tribe_get_ticket_label_plural( "tickets_{$provider->orm_provider}_success_page_your_tickets" )
	); ?>
</h4>

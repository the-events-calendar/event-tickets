<?php
/**
 * Tickets Commerce: Success Order Page Attendee list.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/attendees.php
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

<div class="tribe-common tribe-common-b1 tec-tickets__attendees-list-order-attendees">
	<?php $this->template( 'order/attendees/title' ); ?>

	<div class="tec-tickets__attendees-list-listing">
		<?php foreach ( $attendees as $attendee ) : ?>
			<div class="tec-tickets__attendees-list-attendee-row">
				<?php $this->template( 'order/attendees/attendee', [ 'attendee' => $attendee ] ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

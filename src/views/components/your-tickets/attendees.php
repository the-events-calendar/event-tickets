<?php
/**
 * Generic: Success Order Page Attendee list (Your Tickets).
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/components/your-tickets/attendees.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
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

$parent_classes = [
	'tribe-common',
	'tribe-common-b1',
	'tec-tickets__attendees-list-order-attendees',
	$provider->orm_provider
]
?>

<div <?php tribe_classes( $parent_classes ); ?>>
	<?php $this->template( 'your-tickets/attendees/title' ); ?>

	<div class="tec-tickets__attendees-list-listing">
		<?php foreach ( $attendees as $attendee ) : ?>
			<div class="tec-tickets__attendees-list-attendee-row">
				<?php $this->template( 'your-tickets/attendees/attendee', [ 'attendee' => $attendee ] ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

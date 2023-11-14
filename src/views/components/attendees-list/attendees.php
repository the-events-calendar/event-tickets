<?php
/**
 * Generic: Success Order Page Attendee list.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/components/attendees-list/attendees.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.7.0
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

$wrapper_classes = [
	'tribe-common',
	'tribe-common-b1',
	'tec-tickets__attendees-list-wrapper',
	'tec-tickets__attendees-list-wrapper--' . $provider->orm_provider,
];
?>

<div <?php tribe_classes( $wrapper_classes ); ?>>
	<?php $this->template( 'components/attendees-list/title' ); ?>

	<div class="tec-tickets__attendees-list">
		<?php foreach ( $attendees as $attendee ) : ?>
			<div class="tec-tickets__attendees-list-item">
				<?php $this->template( 'components/attendees-list/attendees/attendee', [ 'attendee' => $attendee ] ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>

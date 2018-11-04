<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * @version TBD
 *
 */

if (
	! class_exists( 'Tribe__Tickets_Plus__Meta' )
	|| ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' )
) {
	return;
}

$storage            = new Tribe__Tickets_Plus__Meta__Storage();
$meta               = tribe( 'tickets-plus.main' )->meta();
$current_ticket_id  = 0;
$i                  = 0;
$checkout_url       = tribe( 'tickets.attendee_registration' )->get_checkout_url();
$is_meta_up_to_date = (int) apply_filters( 'tribe_tickets_attendee_registration_is_meta_up_to_date', true );

if ( empty( $events ) ) {
	esc_html_e( 'You currently have no events awaiting registration', 'event-tickets' );
}

?>

<?php foreach ( $events as $event_id => $ticket ) : ?>

<div class="tribe-block__tickets__registration" data-is-meta-up-to-date="<?php echo absint( $is_meta_up_to_date ); ?>">

	<?php $this->template( 'summary/content', array( 'event_id' => $event_id, 'tickets' => $ticket ) ); ?>

	<div class="tribe-block__tickets__item__attendee__fields">
		<form method="post" class="tribe-block__tickets__item__attendee__fields__form" name="<?php echo 'event' . $event_id ?>">
			<?php $this->template( 'attendees/content', array( 'event_id' => $event_id, 'tickets' => $ticket ) ); ?>
			<input type="hidden" name="tribe_tickets_saving_attendees" value="1"/>
			<button type="submit"><?php _e( 'Save Attendee Info', 'event-tickets' ); ?></button>
		</form>
	</div>

</div>
<?php endforeach; ?>

<?php if ( ! empty( $checkout_url ) ): ?>
	<form action="<?php echo esc_url( $checkout_url ); ?>" method="get">
		<button type="submit" class="alignright button-primary">Checkout</button>
	</form>
<?php endif; ?>
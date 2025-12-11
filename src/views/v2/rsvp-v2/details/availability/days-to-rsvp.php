<?php
/**
 * RSVP V2: Days Until Close
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/availability/days-to-rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket       The RSVP ticket object.
 * @var int                           $post_id      The event post ID.
 * @var int                           $days_to_rsvp Days until RSVP closes.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

if ( 0 < $days_to_rsvp ) {
	$text = sprintf(
		// Translators: 1: opening span. 2: the number of remaining days to RSVP. 3: Closing span. 4: The RSVP label.
		_nx(
			'%1$s %2$s %3$s day left to %4$s',
			'%1$s %2$s %3$s days left to %4$s',
			$days_to_rsvp,
			'Days to RSVP',
			'event-tickets'
		),
		'<span class="tribe-tickets__rsvp-v2-availability-days-left tribe-common-b2--bold">',
		number_format_i18n( $days_to_rsvp ),
		'</span>',
		tribe_get_rsvp_label_singular( 'Days to RSVP' )
	);
} else {
	$text = sprintf(
		// Translators: %s: The RSVP label.
		_x(
			'Last day to %s',
			'Last day to RSVP',
			'event-tickets'
		),
		tribe_get_rsvp_label_singular( 'Last day to RSVP' )
	);
}
?>

<span class="tribe-tickets__rsvp-v2-availability-days-to-rsvp">
	<?php echo wp_kses_post( $text ); ?>
</span>

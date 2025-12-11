<?php
/**
 * RSVP V2: Main RSVP Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
 * @var int                           $post_id The event post ID.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$classes = [
	'tribe-tickets__rsvp-v2-wrapper',
	'tribe-common-g-row',
	'tribe-common-g-row--gutters',
];

/**
 * Filters the CSS classes for the RSVP V2 wrapper.
 *
 * @since TBD
 *
 * @param array                           $classes Array of CSS classes.
 * @param Tribe__Tickets__Ticket_Object   $ticket  The RSVP ticket object.
 * @param int                             $post_id The event post ID.
 */
$classes = apply_filters( 'tec_tickets_rsvp_v2_wrapper_classes', $classes, $ticket, $post_id );
?>
<div
	<?php tribe_classes( $classes ); ?>
	data-rsvp-v2-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-rsvp-v2-post-id="<?php echo esc_attr( $post_id ); ?>"
>
	<?php $this->template( 'v2/rsvp-v2/content', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
</div>

<?php
/**
 * RSVP V2: Success Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/success/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket    The RSVP ticket object.
 * @var int                           $post_id   The event post ID.
 * @var bool                          $is_going  Whether user is going or not going.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$success_text = ! empty( $is_going ) ? __( 'You are going', 'event-tickets' ) : __( "Can't go", 'event-tickets' );
?>
<div class="tribe-tickets__rsvp-v2-actions-success-going">
	<em class="tribe-tickets__rsvp-v2-actions-success-going-check-icon"></em>
	<span class="tribe-tickets__rsvp-v2-actions-success-going-text tribe-common-h4 tribe-common-h6--min-medium">
		<?php echo esc_html( $success_text ); ?>
	</span>
</div>

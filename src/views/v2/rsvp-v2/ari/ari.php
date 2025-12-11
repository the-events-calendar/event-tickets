<?php
/**
 * RSVP V2: ARI Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/ari/ari.php
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
?>
<form
	class="tribe-tickets__rsvp-v2-ar tribe-common-g-row tribe-common-g-row--gutters"
	name="tribe-tickets-rsvp-v2-form-ari"
	data-rsvp-v2-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-rsvp-v2-post-id="<?php echo esc_attr( $post_id ); ?>"
>
	<div class="tribe-tickets__rsvp-v2-ar-sidebar-wrapper tribe-common-g-col">
		<?php $this->template( 'v2/rsvp-v2/ari/sidebar/sidebar', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
	</div>

	<div class="tribe-tickets__rsvp-v2-ar-form-wrapper tribe-common-g-col">
		<?php $this->template( 'v2/rsvp-v2/ari/form/form', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
	</div>
</form>

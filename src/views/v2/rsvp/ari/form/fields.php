<?php
/**
 * This template renders the RSVP AR form fields.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/fields.php
 *
 * @since TBD
 *
 * @version TBD
 */

$has_meta = get_post_meta( $rsvp->ID, '_tribe_tickets_meta_enabled', true );

if ( empty( $has_meta ) ) {
	return;
}
?>
<div class="tribe-tickets__form">

	<?php $this->template( 'v2/rsvp/ari/form/error', [ 'rsvp' => $rsvp ] ); ?>

	<?php
		/**
		 * Allows injection of meta fields in the RSVP ARI form.
		 *
		 * @since TBD
		 *
		 * @var bool|WP_Post
		 * @var Tribe__Tickets__Ticket_Object
		 */
		do_action( 'tribe_tickets_rsvp_attendee_fields', $post_id, $rsvp );
	?>

</div>

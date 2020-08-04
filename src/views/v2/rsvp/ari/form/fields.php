<?php
/**
 * This template renders the RSVP AR form fields.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/fields.php
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var int $post_id The post ID the RSVP is linked to.
 *
 * @since TBD
 *
 * @version TBD
 */

/**
 * Filter to check if the RSVP has meta.
 *
 * @since TBD
 *
 * @param Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @return bool
 */
$has_meta = apply_filters( 'tribe_tickets_rsvp_has_meta', $rsvp, false );

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
		 * @see  Tribe__Template\do_entry_point()
		 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
		 */
		$this->do_entry_point( 'rsvp_attendee_fields' );
	?>

</div>

<?php
/**
 * Block: RSVP
 * Actions - RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 * @version TBD
 */

?>
<div class="tribe-tickets__rsvp-actions-rsvp">
	<span class="tribe-common-h6">
		<?php esc_html_e( 'RSVP Here', 'event-tickets' ); ?>
	</span>

	<?php $this->template( 'v2/rsvp/actions/rsvp/going', [ 'rsvp' => $rsvp ] ); ?>

	<?php $this->template( 'v2/rsvp/actions/rsvp/not-going', [ 'rsvp' => $rsvp ] ); ?>

</div>

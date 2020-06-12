<?php
/**
 * Block: RSVP
 * Actions - RSVP - Going
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/actions/rsvp/going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var bool $must_login Whether the user has to login to RSVP or not.
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 * @version TBD
 */

?>

<div class="tribe-tickets__rsvp-actions-rsvp-going">
	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-actions-button-going"
		type="submit"
		<?php tribe_disabled( $must_login ); ?>
	><?php esc_html_e( 'Going', 'event-tickets' ); ?></button>
</div>

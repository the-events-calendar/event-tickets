<?php
/**
 * Block: RSVP
 * Inactive Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/content-inactive.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$message = $this->get( 'active_past' ) ? esc_html__( 'RSVPs are no longer available', 'event-tickets' ) : esc_html__( 'RSVPs are not yet available', 'event-tickets' );
?>
<div class="tribe-block__rsvp__content tribe-block__rsvp__content--inactive">
	<div class="tribe-block__rsvp__details__status">
		<div class="tribe-block__rsvp__details">
			<?php echo $message ?>
		</div>
	</div>
</div>

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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$message = $this->get( 'all_past' ) ? __( 'RSVPs are no longer available', 'event-tickets' ) : __( 'RSVPs are not yet available', 'event-tickets' );
?>
<div class="tribe-block__rsvp__content tribe-block__rsvp__content--inactive">
	<div class="tribe-block__rsvp__details__status">
		<div class="tribe-block__rsvp__details">
			<?php echo esc_html( $message ) ?>
		</div>
	</div>
</div>

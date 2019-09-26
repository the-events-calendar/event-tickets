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
 * @since TBD Use function for text.
 *
 * @version TBD
 */

$message = $this->get( 'all_past' )
	? sprintf( _x( '%s are no longer available', 'RSVP block inactive content', 'event-tickets' ), tribe_get_rsvp_label_plural( 'block_inactive_content' ) )
	: sprintf( _x( '%s are not yet available', 'RSVP block inactive content', 'event-tickets' ), tribe_get_rsvp_label_plural( 'block_inactive_content' ) );
?>
<div class="tribe-block__rsvp__content tribe-block__rsvp__content--inactive">
	<div class="tribe-block__rsvp__details__status">
		<div class="tribe-block__rsvp__details">
			<?php echo esc_html( $message ) ?>
		</div>
	</div>
</div>
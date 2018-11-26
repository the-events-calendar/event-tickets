<?php
/**
 * Block: RSVP
 * Status Going
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/status/going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */

?>
<span>
	<button class="tribe-block__rsvp__status-button tribe-block__rsvp__status-button--going">
		<?php $this->template( 'blocks/rsvp/status/going-icon' ); ?>
		<span><?php esc_html_e( 'Going', 'events-gutenberg' ); ?></span>
	</button>
</span>
<?php
/**
 * Block: RSVP
 * Icon
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/icon.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since TBD Uses new functions to get singular and plural texts.
 *
 * @version TBD
 */

?>
<div class="tribe-block__rsvp__icon">
	<?php $this->template( 'blocks/rsvp/icon-svg' ); ?>
	<?php echo tribe_get_rsvp_label_singular( basename( __FILE__ ) ) ?>
</div>

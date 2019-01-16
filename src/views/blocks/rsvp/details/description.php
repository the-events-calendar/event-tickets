<?php
/**
 * Block: RSVP
 * Details Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/details/description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

if ( ! $ticket->show_description() ) {
	return;
}
?>
<div class="tribe-block__rsvp__description">
	<?php echo wpautop( esc_html( $ticket->description ) ); ?>
</div>

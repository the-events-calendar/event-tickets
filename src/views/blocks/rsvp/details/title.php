<?php
/**
 * Block: RSVP
 * Details Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/details/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Added a conditional to display/hide the RSVP type..
 *
 * @version TBD
 *
 */

?>
<header class="tribe-block__rsvp__title">
	<?php echo esc_html( ( $ticket->show_type() ? $ticket->name : '' ) ); ?>
</header>

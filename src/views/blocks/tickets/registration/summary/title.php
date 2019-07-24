<?php
/**
 * Block: Tickets
 * Registration Summary Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/summary/title.php
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
<div class="tribe-block__tickets__registration__title">
	<header>
		<h2><?php echo esc_html( sprintf( __( '%s Registration', 'event-tickets' ), tribe_get_ticket_label_singular( basename( __FILE__ ) ) ) ); ?></h2>
	</header>
</div>



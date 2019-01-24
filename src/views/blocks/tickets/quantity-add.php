<?php
/**
 * Block: Tickets
 * Quantity Add
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-add.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.9.4
 *
 */


$ticket = $this->get( 'ticket' );
?>
<button
	class="tribe-block__tickets__item__quantity__add"
>
	<?php esc_html_e( '+', 'event-tickets' ); ?>
</button>

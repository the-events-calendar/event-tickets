<?php
/**
 * Block: Tickets
 * Footer Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/footer-quantity.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */


$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__footer__quantity tribe-common-b2"
>
	<?php echo esc_html_x( 'Quantity:', 'Total selected tickets count.', 'event-tickets' ); ?>
	&nbsp;
	<span class="tribe-block__tickets__item__footer__quantity__number"><?php echo tribe_format_currency( 0 ); ?></span>
</div>

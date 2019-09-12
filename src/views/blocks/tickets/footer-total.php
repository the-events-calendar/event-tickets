<?php
/**
 * Block: Tickets
 * Footer Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/footer-total.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 * @version TBD
 *
 */
?>
<div
	class="tribe-block__tickets__item__footer__total tribe-common-b2"
>
	<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	&nbsp;
	<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ) ?>
</div>

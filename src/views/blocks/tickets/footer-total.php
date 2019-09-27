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
$post_id = $this->get( 'event_id' );
$currency_symbol = $this->get( 'currency_symbol' );
?>
<div class="tribe-common-b2 tribe-tickets__footer__total" >
	<span class="tribe-tickets__footer__total__label">
		<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	</span>
	<span class="tribe-tickets__footer__total__wrap">
		<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ); ?>
	</span>
</div>

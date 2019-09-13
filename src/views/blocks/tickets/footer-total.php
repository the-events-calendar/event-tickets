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

$currency_symbol = $this->get( 'currency_symbol' );
?>
<div class="tribe-common-b2 tribe-tickets__item__footer__total" >
	<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	<span class="tribe-tickets__item__total__wrap">
		<span class="tribe-tickets__item__footer__total__currency-symbol"><?php echo $currency_symbol; ?></span>
		<span class="tribe-tickets__item__footer__total__number"><?php echo tribe_format_currency( 0 ); ?></span>
	</span>
</div>

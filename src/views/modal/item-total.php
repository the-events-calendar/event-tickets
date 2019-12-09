<?php
/**
 * Modal: Item Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/modal/item-total.php
 *
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.11
 *
 * @version 4.11
 */
?>
<div class="tribe-common-b2 tribe-tickets__item__total__wrap" >
	<span class="tribe-tickets__item__total">
		<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ); ?>
	</span>
</div>

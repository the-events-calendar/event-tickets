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
 * @since TBD
 *
 * @version TBD
 * @todo Adjust with #133179
 */
?>
<div class="tribe-common-b2 tribe-block__tickets__item__total__wrap" >
	<span class="tribe-block__tickets__item__total">
		<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ) ?>
	</span>
</div>

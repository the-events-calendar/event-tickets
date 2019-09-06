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
 */
?>
<div
	class="tribe-modal__cart__item__total__wrap"
>
	<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ) ?>
</div>

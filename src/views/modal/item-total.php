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
 *
 */
?>
<div
	class="tribe-modal__cart__item__total__wrap"
>
	<span class="tribe-modal__cart__item__total__currency__symbol"><?php echo tribe_get_option( 'defaultCurrencySymbol', '$' ); ?></span>
	<span class="tribe-modal__cart__item__total">0.00</span>
</div>

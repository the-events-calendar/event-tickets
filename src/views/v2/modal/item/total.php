<?php
/**
 * Modal: Item Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/item/total.php
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var bool $is_mini  True if it's in mini cart context.
 * @var bool $is_modal True if it's in modal context.
 */

// Bail if it's NOT in modal and mini cart context.
if (
	empty( $is_modal )
	&& empty( $is_mini )
) {
	return;
}

/** @var Tribe__Tickets__Commerce__Currency $tribe_commerce_currency */
$tribe_commerce_currency = tribe( 'tickets.commerce.currency' );
?>
<div class="tribe-common-b2 tribe-tickets__item__total__wrap">
	<span class="tribe-tickets__item__total">
		<?php echo $tribe_commerce_currency->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ); ?>
	</span>
</div>

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
 * @var bool                               $is_mini  True if it's in mini cart context.
 * @var bool                               $is_modal True if it's in modal context.
 * @var int                                $post_id  The post/event ID.
 * @var Tribe__Tickets__Commerce__Currency $currency The currency class.
 * @var Tribe__Tickets__Tickets            $provider The tickets provider class.
 */

// Bail if it's NOT in modal and mini cart context.
if (
	empty( $is_modal )
	&& empty( $is_mini )
) {
	return;
}

?>
<div class="tribe-common-b2 tribe-tickets__item__total__wrap">
	<span class="tribe-tickets__item__total">
		<?php echo $currency->get_formatted_currency_with_symbol( 0, $post_id, $provider->class_name ); ?>
	</span>
</div>

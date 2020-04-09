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
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.11.0
 * @since   4.11.3 Updated code comments.
 *
 * @version 4.11.3
 */
$post_id = $this->get( 'event_id' );

$currency_symbol = $this->get( 'currency_symbol' );

if ( is_object( $provider ) ) {
	$provider_class = $provider->class_name;
} else {
	$provider_class = '';
}

/** @var Tribe__Tickets__Commerce__Currency $tribe_commerce_currency */
$tribe_commerce_currency = tribe( 'tickets.commerce.currency' );
?>
<div class="tribe-common-b2 tribe-tickets__footer__total">
	<span class="tribe-tickets__footer__total__label">
		<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	</span>
	<span class="tribe-tickets__footer__total__wrap">
		<?php echo $tribe_commerce_currency->get_formatted_currency_with_symbol( 0, $post_id, $provider_class ); ?>
	</span>
</div>
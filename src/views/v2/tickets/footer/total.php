<?php
/**
 * Block: Tickets
 * Footer Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/footer/total.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var WP_Post|int                        $post_id     The post object or ID.
 * @var string                             $provider_id The tickets provider class name.
 * @var Tribe__Tickets__Commerce__Currency $currency
 */

?>
<div class="tribe-common-b2 tribe-tickets__footer__total">
	<span class="tribe-tickets__footer__total__label">
		<?php echo esc_html_x( 'Total:', 'Total selected tickets price.', 'event-tickets' ); ?>
	</span>
	<span class="tribe-tickets__footer__total__wrap">
		<?php echo $currency->get_formatted_currency_with_symbol( 0, $post_id, $provider_id ); ?>
	</span>
</div>

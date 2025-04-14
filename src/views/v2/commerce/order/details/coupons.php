<?php
/**
 * Tickets Commerce: Success Order Page Details > Coupons
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/details/coupons.php
 *
 * @since 5.21.0
 *
 * @version 5.21.0
 *
 * @var Tribe__Template $this  [Global] Template object.
 * @var WP_Post         $order [Global] The order object.
 */

declare( strict_types=1 );

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;

// If there are no coupons, we don't need to display anything.
if ( empty( $order->coupons ) ) {
	return;
}

$discounts = array_map(
	fn( Value $value ) => Legacy_Value_Factory::to_currency_value( $value ),
	wp_list_pluck( array_values( $order->coupons ), 'sub_total' )
);

$total_discount = Currency_Value::sum( ...$discounts );

?>
<div class="tribe-tickets__commerce-order-details-row">
	<div class="tribe-tickets__commerce-order-details-col1">
		<?php esc_html_e( 'Discount:', 'event-tickets' ); ?>
	</div>
	<div class="tribe-tickets__commerce-order-details-col2">
		<?php echo esc_html( $total_discount->get() ); ?>
	</div>
</div>

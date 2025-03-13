<?php
/**
 * Tickets Commerce: Success Order Page Details > Fees
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/order/details/fees.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Template $this  [Global] Template object.
 * @var WP_Post         $order [Global] The order object.
 */

declare( strict_types=1 );

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;

// If there are no fees, we don't need to display anything.
if ( empty( $order->fees ) ) {
	return;
}

$fees = array_map(
	fn( Value $value ) => Legacy_Value_Factory::to_currency_value( $value ),
	wp_list_pluck( $order->fees, 'sub_total' )
);

$total_fees = Currency_Value::sum( ...$fees );

?>
<div class="tribe-tickets__commerce-order-details-row">
	<div class="tribe-tickets__commerce-order-details-col1">
		<?php esc_html_e( 'Fees:', 'event-tickets' ); ?>
	</div>
	<div class="tribe-tickets__commerce-order-details-col2">
		<?php echo esc_html( $total_fees->get() ); ?>
	</div>
</div>

<?php
/**
 * Coupons class for handling PayPal coupons.
 *
 * This class manages the addition and calculation of coupons within the PayPal gateway workflow.
 *
 * @since 5.21.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use TEC\Tickets\Commerce\Values\Precision_Value;
use WP_Post;

/**
 * Class Coupons
 *
 * @since 5.21.0
 */
class Coupons extends Controller_Contract {

	use Type;

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'add_coupon_unit_data_to_paypal' ],
			20,
			2
		);
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since 5.21.0
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'add_coupon_unit_data_to_paypal' ],
			20
		);
	}

	/**
	 * Adds coupon unit data to the PayPal order.
	 *
	 * @since 5.21.0
	 *
	 * @param array   $unit  The unit data to be passed to PayPal.
	 * @param WP_Post $order The order object.
	 *
	 * @return array
	 */
	public function add_coupon_unit_data_to_paypal( array $unit, WP_Post $order ) {
		if ( empty( $order->coupons ) ) {
			return $unit;
		}

		/*
		 * PayPal doesn't support negative amount for items like Stripe. So, we need
		 * to do the following to add a discount:
		 *
		 * 1. Get the coupon values and add them together.
		 * 2. Convert the total to a positive number.
		 * 3. Add the total to the extra_breakdown field.
		 * 4. Update the item total to reflect the total PRIOR to discount.
		 */

		$values = [];
		foreach ( $order->coupons as $coupon ) {
			$values[] = Legacy_Value_Factory::to_precision_value( $coupon['sub_total'] );
		}

		$total = Precision_Value::sum( ...$values )->invert_sign();

		// Set up the extra breakdown data.
		if ( ! array_key_exists( 'extra_breakdown', $unit ) ) {
			$unit['extra_breakdown'] = [];
		}

		$unit['extra_breakdown']['discount'] = [
			'currency_code' => $order->currency,
			'value'         => (string) $total,
		];

		// Update the item total to reflect the total PRIOR to discount.
		$item_values = array_map(
			static fn( $item ) => new Precision_Value( $item['sub_total'] ),
			$order->items
		);

		// Include fees in the item total if present.
		if ( ! empty( $order->fees ) ) {
			$fee_values = array_map(
				static fn( $fee ) => new Precision_Value( $fee['sub_total'] ),
				$order->fees
			);

			$item_values = array_merge( $item_values, $fee_values );
		}

		$unit['item_value'] = (string) Precision_Value::sum( ...$item_values );

		return $unit;
	}
}

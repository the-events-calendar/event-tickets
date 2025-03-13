<?php
/**
 * Coupons class for handling PayPal coupons.
 *
 * This class manages the addition and calculation of coupons within the PayPal gateway workflow.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Traits\Type;
use WP_Post;

/**
 * Class Coupons
 *
 * @since TBD
 */
class Coupons extends Controller_Contract {

	use Type;

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'add_coupon_unit_data_to_paypal' ],
			10,
			2
		);
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since TBD
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'add_coupon_unit_data_to_paypal' ],
		);
	}

	/**
	 * Adds coupon unit data to the PayPal order.
	 *
	 * @since TBD
	 *
	 * @param array   $unit  The unit data to be passed to PayPal.
	 * @param WP_Post $order The order object.
	 *
	 * @return array
	 */
	public function add_coupon_unit_data_to_paypal( array $unit, WP_Post $order ) {
		if ( empty ( $order->coupons ) ) {
			return $unit;
		}

		foreach ( $order->coupons as $coupon ) {
			if ( is_int( $coupon['id'] ) ) {
				$sku = $this->get_unique_type_id( $coupon['id'], 'coupon' );
			} else {
				$sku = $coupon['id'];
			}

			$unit['items'][] = [
				'name'        => $coupon['slug'],
				'quantity'    => $coupon['quantity'] ?? 1,
				'unit_amount' => [
					'value'         => $coupon['sub_total'],
					'currency_code' => $order->currency,
				],
				'item_total'  => [
					'value'         => $coupon['sub_total'],
					'currency_code' => $order->currency,
				],
				'sku'         => $sku,
			];
		}

		return $unit;
	}
}

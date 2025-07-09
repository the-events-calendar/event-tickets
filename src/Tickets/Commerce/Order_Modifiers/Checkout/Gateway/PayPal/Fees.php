<?php
/**
 * Fees Class for handling fee logic in the PayPal checkout process.
 *
 * This class manages fees, calculates them, and appends them to the cart
 * during the PayPal checkout process. It integrates with various filters
 * and hooks specific to PayPal's order and payment flow.
 *
 * @since 5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Paypal
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Abstract_Fees;
use TEC\Tickets\Commerce\Values\Precision_Value;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic specifically for the PayPal checkout process.
 * This class manages the addition and calculation of fees within the
 * PayPal gateway workflow.
 *
 * @since 5.18.0
 */
class Fees extends Abstract_Fees {

	/**
	 * Registers the necessary hooks for adding and managing fees in PayPal checkout.
	 *
	 * This includes calculating total fees, modifying cart values, and displaying
	 * fee sections during the PayPal checkout process.
	 *
	 * @since 5.18.0
	 */
	public function do_register(): void {
		// Hook for adding fee unit data to PayPal order.
		add_action(
			'tec_tickets_commerce_paypal_order_get_unit_data_fee',
			[ $this, 'add_fee_unit_data_to_paypal' ],
			10,
			2
		);

		// Trigger the fees to be added to the other items.
		add_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'inclue_fees_with_items' ],
			10,
			3
		);
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action(
			'tec_tickets_commerce_paypal_order_get_unit_data_fee',
			[ $this, 'add_fee_unit_data_to_paypal' ],
		);

		remove_filter(
			'tec_tickets_commerce_paypal_order_unit',
			[ $this, 'inclue_fees_with_items' ],
		);
	}

	/**
	 * Includes fees with the items in the PayPal order.
	 *
	 * @since 5.21.0
	 *
	 * @param array          $unit     The current unit data.
	 * @param WP_Post        $order    The current order object.
	 * @param Order_Endpoint $endpoint The order endpoint object.
	 *
	 * @return array
	 */
	public function inclue_fees_with_items( array $unit, WP_Post $order, Order_Endpoint $endpoint ) {
		if ( empty( $order->fees ) ) {
			return $unit;
		}

		foreach ( $order->fees as $fee ) {
			$unit['items'][] = $endpoint->get_unit_data( $fee, $order );
		}

		return $unit;
	}

	/**
	 * Adds the unit data for a fee item in PayPal orders.
	 *
	 * This method structures the fee item data in a format compatible with PayPal's
	 * API. It includes details like the display name, price, currency, quantity,
	 * and SKU.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $item The cart item representing the fee.
	 * @param WP_Post $order The current order object associated with the PayPal transaction.
	 *
	 * @return array The structured unit data for the fee item to be sent to PayPal.
	 */
	public function add_fee_unit_data_to_paypal( array $item, WP_Post $order ) {
		return [
			'name'        => $item['display_name'],
			'unit_amount' => [
				'value'         => (string) ( new Precision_Value( $item['price'] ) ),
				'currency_code' => $order->currency,
			],
			// Fees should be added as many times as the items.
			'quantity'    => $item['quantity'] ?? 1,
			'item_total'  => [
				'value'         => (string) ( new Precision_Value( $item['sub_total'] ) ),
				'currency_code' => $order->currency,
			],
			'sku'         => "fee-{$item['fee_id']}",
		];
	}
}

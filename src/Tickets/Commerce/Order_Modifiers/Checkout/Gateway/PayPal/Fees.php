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

use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Abstract_Fees;
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
		// Hook for appending fees to the cart for PayPal processing.
		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'append_fees_to_cart' ],
			10,
			2
		);

		// Hook for adding fee unit data to PayPal order.
		add_action(
			'tec_tickets_commerce_paypal_order_get_unit_data_fee',
			[ $this, 'add_fee_unit_data_to_paypal' ],
			10,
			2
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
		remove_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'append_fees_to_cart' ],
		);

		remove_action(
			'tec_tickets_commerce_paypal_order_get_unit_data_fee',
			[ $this, 'add_fee_unit_data_to_paypal' ],
		);
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
				'value'         => (string) $item['price'],
				'currency_code' => $order->currency,
			],
			// Fees should be added as many times as the items.
			'quantity'    => $item['quantity'] ?? 1,
			'item_total'  => [
				'value'         => (string) $item['sub_total'],
				'currency_code' => $order->currency,
			],
			'sku'         => "fee-{$item['fee_id']}",
		];
	}
}

<?php
/**
 * Fees Class for handling fee logic in the PayPal checkout process.
 *
 * This class manages fees, calculates them, and appends them to the cart
 * during the PayPal checkout process. It integrates with various filters
 * and hooks specific to PayPal's order and payment flow.
 *
 * @since TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Paypal
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Paypal;

use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Abstract_Fees;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic specifically for the PayPal checkout process.
 * This class manages the addition and calculation of fees within the
 * PayPal gateway workflow.
 *
 * @since TBD
 */
class Fees extends Abstract_Fees {

	/**
	 * Registers the necessary hooks for adding and managing fees in PayPal checkout.
	 *
	 * This includes calculating total fees, modifying cart values, and displaying
	 * fee sections during the PayPal checkout process.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Hook for appending fees to the cart for PayPal processing.
		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			$this->get_fee_append_callback(),
			10,
			2
		);

		// Hook for adding fee unit data to PayPal order.
		add_action(
			'tec_commerce_paypal_order_get_unit_data_fee',
			$this->get_fee_unit_data_paypal_callback(),
			10,
			2
		);
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			$this->get_fee_append_callback()
		);

		remove_action(
			'tec_commerce_paypal_order_get_unit_data_fee',
			$this->get_fee_unit_data_paypal_callback()
		);
	}

	/**
	 * Adds the unit data for a fee item in PayPal orders.
	 *
	 * This method structures the fee item data in a format compatible with PayPal's
	 * API. It includes details like the display name, price, currency, quantity,
	 * and SKU.
	 *
	 * @since TBD
	 *
	 * @param array   $item The cart item representing the fee.
	 * @param WP_Post $order The current order object associated with the PayPal transaction.
	 *
	 * @return array The structured unit data for the fee item to be sent to PayPal.
	 */
	protected function add_fee_unit_data_to_paypal( array $item, WP_Post $order ) {
		return [
			'name'        => $item['display_name'],
			'unit_amount' => [
				'value'         => (string) $item['price'],
				'currency_code' => $order->currency,
			],
			// Fees always have a quantity of 1, and they need to be a string for the API.
			'quantity'    => '1',
			'item_total'  => [
				'value'         => (string) $item['sub_total'],
				'currency_code' => $order->currency,
			],
			'sku'         => "fee-{$item['fee_id']}",
		];
	}

	/**
	 * Get the callback for adding fee unit data to PayPal orders.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for adding fee unit data to PayPal orders.
	 */
	protected function get_fee_unit_data_paypal_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( $item, $order ) => $this->add_fee_unit_data_to_paypal( $item, $order );
		}

		return $callback;
	}
}
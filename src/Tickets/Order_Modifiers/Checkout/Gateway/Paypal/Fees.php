<?php
/**
 * Fees Class for handling fee logic in the PayPal checkout process.
 *
 * This class manages fees, calculates them, and appends them to the cart
 * during the PayPal checkout process. It integrates with various filters
 * and hooks specific to PayPal's order and payment flow.
 *
 * @since TBD
 * @package TEC\Tickets\Order_Modifiers\Checkout\Gateway\Paypal
 */

namespace TEC\Tickets\Order_Modifiers\Checkout\Gateway\Paypal;

use TEC\Tickets\Order_Modifiers\Checkout\Abstract_Fees;
use TEC\Tickets\Registerable;
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
class Fees extends Abstract_Fees implements Registerable {

	/**
	 * Registers the necessary hooks for adding and managing fees in PayPal checkout.
	 *
	 * This includes calculating total fees, modifying cart values, and displaying
	 * fee sections during the PayPal checkout process.
	 *
	 * @since TBD
	 */
	public function register(): void {
		// Hook for appending fees to the cart for PayPal processing.
		add_action( 'tec_tickets_commerce_create_from_cart_items', [ $this, 'append_fees_to_cart' ], 10, 4 );

		// Hook for adding fee unit data to PayPal order.
		add_action( 'tec_commerce_paypal_order_get_unit_data_fee', [ $this, 'add_fee_unit_data_to_paypal' ], 10, 2 );
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
	public function add_fee_unit_data_to_paypal( array $item, WP_Post $order ) {
		return [
			'name'        => $item['display_name'],
			'unit_amount' => [
				'value'         => (string) $item['price'],
				'currency_code' => $order->currency,
			],
			'quantity'    => '1', // Fees always have a quantity of 1.
			'item_total'  => [
				'value'         => (string) $item['sub_total'],
				'currency_code' => $order->currency,
			],
			'sku'         => 'fee-' . $item['fee_id'],
		];
	}
}

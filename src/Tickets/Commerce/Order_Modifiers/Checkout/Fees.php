<?php
/**
 * Fees Class for handling fee logic during the checkout process.
 *
 * This class is responsible for managing fees, calculating them, and appending them to the cart,
 * independent of the payment gateway in use. It integrates with various filters and hooks during
 * the checkout process to ensure consistent fee handling.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Tickets\Registerable;

/**
 * Class Fees
 *
 * This class manages the addition of fees to the cart and checkout flow, ensuring that the fee logic
 * is applied across different gateways. The fees are calculated and displayed independently of the gateway
 * and integrated into the cart total.
 *
 * @since TBD
 */
class Fees extends Abstract_Fees implements Registerable {

	/**
	 * Registers the necessary hooks for adding and managing fees during the checkout process.
	 *
	 * This method hooks into the calculation of total values, displays fee sections in the checkout,
	 * and modifies the total values accordingly, ensuring that the fee logic is applied agnostically
	 * across different payment gateways.
	 *
	 * @since TBD
	 */
	public function register(): void {
		// Hook for calculating total values, setting subtotal, and modifying the total value.
		add_filter(
			'tec_tickets_commerce_get_cart_total_value',
			fn( $values, $items, $subtotal ) => $this->calculate_fees( $values, $items, $subtotal ),
			...$this->hook_args['ten_three']
		);

		// Hook for displaying fees in the checkout.
		add_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			fn( $post, $items, $template ) => $this->display_fee_section( $items, $template ),
			30,
			3
		);
	}
}
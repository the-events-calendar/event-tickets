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

/**
 * Class Fees
 *
 * This class manages the addition of fees to the cart and checkout flow, ensuring that the fee logic
 * is applied across different gateways. The fees are calculated and displayed independently of the gateway
 * and integrated into the cart total.
 *
 * @since TBD
 */
class Fees extends Abstract_Fees {

	/**
	 * Registers the necessary hooks for adding and managing fees during the checkout process.
	 *
	 * This method hooks into the calculation of total values, displays fee sections in the checkout,
	 * and modifies the total values accordingly, ensuring that the fee logic is applied agnostically
	 * across different payment gateways.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Hook for calculating total values, setting subtotal, and modifying the total value.
		add_filter(
			'tec_tickets_commerce_get_cart_total_value',
			$this->get_calculate_fees_callback(),
			10,
			3
		);

		// Hook for displaying fees in the checkout.
		add_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			$this->get_footer_quantity_callback(),
			30,
			3
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
			'tec_tickets_commerce_get_cart_total_value',
			$this->get_calculate_fees_callback()
		);

		remove_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			$this->get_footer_quantity_callback(),
			30
		);
	}

	/**
	 * Get the callback for calculating fees.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for calculating fees.
	 */
	protected function get_calculate_fees_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( $values, $items, $subtotal ) => $this->calculate_fees( $values, $items, $subtotal );
		}

		return $callback;
	}

	/**
	 * Get the callback for displaying the fee section in the checkout.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for displaying the fee section in the checkout.
	 */
	protected function get_footer_quantity_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( $post, $items, $template ) => $this->display_fee_section( $items, $template );
		}

		return $callback;
	}
}

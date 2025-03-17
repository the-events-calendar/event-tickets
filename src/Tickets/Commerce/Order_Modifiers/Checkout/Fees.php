<?php
/**
 * Fees Class for handling fee logic during the checkout process.
 *
 * This class is responsible for managing fees, calculating them, and appending them to the cart,
 * independent of the payment gateway in use. It integrates with various filters and hooks during
 * the checkout process to ensure consistent fee handling.
 *
 * @since   5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use WP_Post;

/**
 * Class Fees
 *
 * This class manages the addition of fees to the cart and checkout flow, ensuring that the fee logic
 * is applied across different gateways. The fees are calculated and displayed independently of the gateway
 * and integrated into the cart total.
 *
 * @since 5.18.0
 */
class Fees extends Abstract_Fees {

	/**
	 * Registers the necessary hooks for adding and managing fees during the checkout process.
	 *
	 * This method hooks into the calculation of total values, displays fee sections in the checkout,
	 * and modifies the total values accordingly, ensuring that the fee logic is applied agnostically
	 * across different payment gateways.
	 *
	 * @since 5.18.0
	 */
	public function do_register(): void {
		// Hook for calculating total values, setting subtotal, and modifying the total value.
		add_filter(
			'tec_tickets_commerce_get_cart_additional_values',
			[ $this, 'calculate_fees' ],
			10,
			3
		);

		// Hook for displaying fees in the checkout.
		add_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[ $this, 'display_fee_section' ],
			30,
			3
		);

		// Attach fees to the order object.
		add_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_fees_to_order_object' ],
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
			'tec_tickets_commerce_get_cart_additional_values',
			[ $this, 'calculate_fees' ]
		);

		remove_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[ $this, 'display_fee_section' ],
			30
		);

		remove_filter(
			'tribe_post_type_tc_orders_properties',
			[ $this, 'attach_fees_to_order_object' ]
		);
	}

	/**
	 * Add fees to the order object properties.
	 *
	 * @since 5.21.0
	 *
	 * @param array   $properties The properties of the order object.
	 * @param WP_Post $order      The order object.
	 *
	 * @return array The updated properties of the order object.
	 */
	public function attach_fees_to_order_object( array $properties, WP_Post $order ): array {
		// There shouldn't be an order with no items, but let's just be safe.
		$items = $properties['items'] ?? [];
		if ( empty( $items ) ) {
			return $properties;
		}

		// We need to normalize the fees for the order object.
		$properties['fees'] = array_map(
			static function ( $fee ) {
				$fee['sub_total'] = Legacy_Value_Factory::to_legacy_value( $fee['fee_amount'] );

				return $fee;
			},
			$this->get_combined_fees_for_items( $items )
		);

		return $properties;
	}
}

<?php
/**
 * Fees Class for handling fee logic in the Stripe checkout process.
 *
 * This class manages fees, calculates them, and appends them to the cart
 * during the Stripe checkout process. It integrates with various filters
 * and hooks specific to Stripe's order and payment flow.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Abstract_Fees;
use TEC\Tickets\Registerable;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic specifically for the Stripe checkout process.
 * This class manages the addition and calculation of fees within the
 * Stripe gateway workflow.
 *
 * @since TBD
 */
class Fees extends Abstract_Fees implements Registerable {

	/**
	 * Registers the necessary hooks for adding and managing fees in Stripe checkout.
	 *
	 * This includes calculating total fees, modifying cart values, and displaying
	 * fee sections during the Stripe checkout process.
	 *
	 * @since TBD
	 */
	public function register(): void {
		// Hook for appending fees to the cart for Stripe processing.
		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			$this->get_fee_append_callback(),
			10,
			2
		);

		add_filter(
			'tec_tickets_commerce_stripe_create_from_cart',
			$this->get_fee_data_stripe_callback(),
			10,
			2
		);

		add_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			$this->get_fee_meta_stripe_callback(),
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

		remove_filter(
			'tec_tickets_commerce_stripe_create_from_cart',
			$this->get_fee_data_stripe_callback()
		);

		remove_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			$this->get_fee_meta_stripe_callback()
		);
	}

	/**
	 * Appends the calculated fees to the cart for Stripe processing.
	 *
	 * This method modifies the value passed in by adding the total fees calculated
	 * from the items in the cart. If no fees exist, the original value is returned.
	 *
	 * @since TBD
	 *
	 * @param Value $value The current value (subtotal) in the cart.
	 * @param array $items The items currently in the cart.
	 *
	 * @return Value Updated value including fees, or the original value if no fees exist.
	 */
	protected function append_fees_to_cart_stripe( Value $value, array $items ): Value {
		// Set the class-level subtotal to the current cart value.
		$this->subtotal = $value;

		// If no items exist in the cart, return the original value.
		if ( empty( $items ) ) {
			return $value;
		}

		// Fetch the combined fees associated with the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// If no combined fees exist, return the original cart value.
		if ( empty( $combined_fees ) ) {
			return $value;
		}

		// Convert each fee_amount to a float using get_decimal() and filter out negative values.
		$combined_fees = array_filter(
			array_map(
				function ( $fee ) {
					if ( isset( $fee['fee_amount'] ) && $fee['fee_amount'] instanceof Value ) {
						$fee['fee_amount'] = $fee['fee_amount']->get_decimal();
					}

					// Return the fee only if the amount is non-negative.
					return $fee['fee_amount'] >= 0 ? $fee : null;
				},
				$combined_fees
			)
		);

		// Return early if all fees are invalid or zero.
		if ( empty( $combined_fees ) ) {
			return $value;
		}

		// Calculate the total fees based on the subtotal and combined fees.
		$sum_of_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees );

		// Return the total value by adding the subtotal and the fees.
		return Value::create()->total( [ $value, $sum_of_fees ] );
	}

	/**
	 * Adds fee metadata to the Stripe payment intent.
	 *
	 * This method processes the fee items in the order and adds them as a string
	 * to the 'fees' metadata field for Stripe. The format of the string is "FeeName: Price".
	 *
	 * @since TBD
	 *
	 * @param array   $metadata       The metadata array to add fees information to.
	 * @param WP_Post $order          The order containing the fee items.
	 *
	 * @return array Updated metadata including the fees as a string.
	 */
	protected function add_meta_data_to_stripe( array $metadata, WP_Post $order ) {
		// Filter out the fee items from the order's items.
		$fee_items = array_filter(
			$order->items,
			function ( $item ) {
				return ! empty( $item['type'] ) && 'fee' === $item['type'];
			}
		);

		$fee_metadata = [];

		// Loop through the fee items and format each one as "FeeName: Price".
		foreach ( $fee_items as $fee_item ) {
			// Skip the fee if it lacks required data or has an invalid price.
			if ( ! isset( $fee_item['display_name'], $fee_item['price'] ) || $fee_item['price'] < 0 ) {
				continue;
			}

			// Format the fee metadata as "FeeName: Price".
			$fee_metadata[] = sprintf( '%s: %.2f', $fee_item['display_name'], $fee_item['price'] );
		}

		if ( ! empty( $fee_metadata ) ) {
			$metadata['fees'] = implode( ', ', $fee_metadata );
		}

		return $metadata;
	}

	/**
	 * Get the callback for appending fees to the cart for Stripe processing.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for appending fees to the cart for Stripe processing.
	 */
	protected function get_fee_data_stripe_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( $value, $items ) => $this->append_fees_to_cart_stripe( $value, $items );
		}

		return $callback;
	}

	/**
	 * Get the callback for adding fee metadata to the Stripe payment intent.
	 *
	 * @since TBD
	 *
	 * @return callable The callback for adding fee metadata to the Stripe payment intent.
	 */
	protected function get_fee_meta_stripe_callback(): callable {
		static $callback = null;
		if ( null === $callback ) {
			$callback = fn( $metadata, $order ) => $this->add_meta_data_to_stripe( $metadata, $order );
		}

		return $callback;
	}
}

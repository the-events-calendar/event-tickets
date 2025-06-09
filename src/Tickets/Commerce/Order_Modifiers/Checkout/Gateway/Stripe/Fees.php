<?php
/**
 * Fees Class for handling fee logic in the Stripe checkout process.
 *
 * This class manages fees, calculates them, and appends them to the cart
 * during the Stripe checkout process. It integrates with various filters
 * and hooks specific to Stripe's order and payment flow.
 *
 * @since 5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe;

use TEC\Tickets\Commerce\Traits\Type;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Abstract_Fees;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic specifically for the Stripe checkout process.
 * This class manages the addition and calculation of fees within the
 * Stripe gateway workflow.
 *
 * @since 5.18.0
 */
class Fees extends Abstract_Fees {

	use Type;

	/**
	 * Registers the necessary hooks for adding and managing fees in Stripe checkout.
	 *
	 * This includes calculating total fees, modifying cart values, and displaying
	 * fee sections during the Stripe checkout process.
	 *
	 * @since 5.18.0
	 */
	public function do_register(): void {
		add_filter(
			'tec_tickets_commerce_stripe_create_from_cart',
			[ $this, 'append_fees_to_cart_stripe' ],
			10,
			2
		);

		add_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ],
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
			'tec_tickets_commerce_stripe_create_from_cart',
			[ $this, 'append_fees_to_cart_stripe' ]
		);

		remove_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ],
		);
	}

	/**
	 * Appends the calculated fees to the cart for Stripe processing.
	 *
	 * This method modifies the value passed in by adding the total fees calculated
	 * from the items in the cart. If no fees exist, the original value is returned.
	 *
	 * @since 5.18.0
	 *
	 * @param Value $value The current value (subtotal) in the cart.
	 * @param array $items The items currently in the cart.
	 *
	 * @return Value Updated value including fees, or the original value if no fees exist.
	 */
	public function append_fees_to_cart_stripe( Value $value, array $items ): Value {
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

		// Calculate the total fees based on the subtotal and combined fees.
		$sum_of_fees = $this->manager->calculate_total_fees( $combined_fees );

		// Return the total value by adding the subtotal and the fees.
		return Value::create()->total( [ $value, $sum_of_fees ] );
	}

	/**
	 * Adds fee metadata to the Stripe payment intent.
	 *
	 * This method processes the fee items in the order and adds them as a string
	 * to the 'fees' metadata field for Stripe. The format of the string is "Fee Name (Quantity): Price".
	 *
	 * @since 5.18.0
	 *
	 * @param array   $metadata The metadata array to add fees information to.
	 * @param WP_Post $order    The order containing the fee items.
	 *
	 * @return array Updated metadata including the fees as a string.
	 */
	public function add_meta_data_to_stripe( array $metadata, WP_Post $order ) {
		// Ensure the order has fees.
		if ( empty( $order->fees ) ) {
			return $metadata;
		}

		// Sort the array alphabetically by display name.
		$fee_items = $order->fees;
		usort(
			$fee_items,
			static function ( $a, $b ) {
				return strcasecmp( $a['display_name'], $b['display_name'] );
			}
		);

		$fee_metadata = [];

		// Loop through the fee items and format each one as "Fee Name (quantity): Subtotal".
		foreach ( $fee_items as $fee_item ) {
			// Skip the fee if it lacks required data or has an invalid price.
			if ( ! isset( $fee_item['display_name'], $fee_item['price'] ) || $fee_item['price'] < 0 ) {
				continue;
			}

			$fee_metadata[] = sprintf(
				'%s (%s): %.2f',
				$fee_item['display_name'],
				$fee_item['quantity'],
				$fee_item['sub_total']
			);
		}

		if ( ! empty( $fee_metadata ) ) {
			$metadata['fees'] = implode( ', ', $fee_metadata );
		}

		return $metadata;
	}
}

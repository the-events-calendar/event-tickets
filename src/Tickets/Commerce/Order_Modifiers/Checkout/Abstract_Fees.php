<?php
/**
 * Fees Class for handling fee logic in the checkout process.
 *
 * This class is responsible for managing fees, calculating them, and appending them
 * to the cart. It integrates with various filters and hooks during the checkout process.
 *
 * @since TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as Fee_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value;
use Tribe__Template as Template;

/**
 * Class Fees
 *
 * Handles fees logic in the checkout process.
 *
 * @since TBD
 */
abstract class Abstract_Fees {

	/**
	 * The default priority and number of arguments for hooks.
	 *
	 * @var array
	 */
	protected array $hook_args = [
		'ten_three' => [ 10, 3 ],
		'ten_two'   => [ 10, 2 ],
	];

	/**
	 * The modifier type used for fees.
	 *
	 * @since TBD
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * The modifier strategy for applying fees.
	 *
	 * @since TBD
	 * @var Modifier_Strategy_Interface
	 */
	protected Modifier_Strategy_Interface $modifier_strategy;

	/**
	 * Manager for handling modifier calculations and logic.
	 *
	 * @since TBD
	 * @var Modifier_Manager
	 */
	protected Modifier_Manager $manager;

	/**
	 * Repository for accessing order modifiers.
	 *
	 * @since TBD
	 * @var Fee_Repository
	 */
	protected Fee_Repository $order_modifiers_repository;

	/**
	 * Repository for accessing and managing relationships between order modifiers.
	 *
	 * @since TBD
	 * @var Order_Modifier_Relationship
	 */
	protected Order_Modifier_Relationship $order_modifiers_relationship_repository;

	/**
	 * Subtotal value used in fee calculations.
	 *
	 * This represents the total amount used as a basis for calculating applicable fees.
	 *
	 * @since TBD
	 * @var Value
	 */
	protected Value $subtotal;

	/**
	 * Tracks whether the fees have already been appended to the cart.
	 *
	 * This static property ensures that the fees are only appended once during the
	 * checkout process across multiple instances of the class. If set to `true`, the
	 * method `append_fees_to_cart` will not add the fees again. The default is `false`,
	 * indicating the fees have not yet been appended.
	 *
	 * @since TBD
	 * @var bool
	 */
	protected static bool $fees_appended = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->modifier_strategy                       = tribe( Controller::class )->get_modifier( $this->modifier_type );
		$this->manager                                 = new Modifier_Manager( $this->modifier_strategy );
		$this->order_modifiers_repository              = new Fee_Repository();
		$this->order_modifiers_relationship_repository = new Order_Modifier_Relationship();
	}

	/**
	 * Registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since TBD
	 */
	abstract public function register();

	/**
	 * Calculates the fees and modifies the total value in the checkout process.
	 *
	 * @since TBD
	 *
	 * @param array $values The existing values being passed through the filter.
	 * @param array $items The items in the cart.
	 * @param Value $subtotal The list of subtotals from the items.
	 *
	 * @return array The updated total values, including the fees.
	 */
	protected function calculate_fees( array $values, array $items, Value $subtotal ): array {
		// Store the subtotal as a class property for later use, encapsulated as a Value object.
		$this->subtotal = $subtotal;

		if ( $this->subtotal->get_integer() <= 0 ) {
			return $values;
		}

		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		if ( empty( $combined_fees ) ) {
			return $values;
		}

		// Calculate the total fees based on the subtotal using Value objects.
		$sum_of_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees );

		// Add the calculated fees to the total value.
		$values[] = $sum_of_fees;

		return $values;
	}

	/**
	 * Displays the fee section in the checkout.
	 *
	 * @since TBD
	 *
	 * @param array    $items    The items in the cart.
	 * @param Template $template The template object for rendering.
	 */
	protected function display_fee_section( array $items, Template $template ): void {
		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// Use the stored subtotal for fee calculations.
		$sum_of_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees )->get_decimal();

		// Convert each fee_amount to an integer using get_integer().
		$combined_fees = array_map(
			function ( $fee ) {
				if ( ! array_key_exists( 'fee_amount', $fee ) ) {
					return $fee;
				}

				if ( $fee['fee_amount'] instanceof Value ) {
					$fee['fee_amount'] = $fee['fee_amount']->get_currency();
				} elseif ( $fee['fee_amount'] instanceof Precision_Value ) {
					$fee['fee_amount'] = new Currency_Value( $fee['fee_amount'] );
				}

				return $fee;
			},
			$combined_fees
		);

		// Pass the fees to the template for display.
		$template->template(
			'checkout/order-modifiers/fees',
			[
				'active_fees' => $combined_fees,
				'sum_of_fees' => $sum_of_fees,
			]
		);
	}

	/**
	 * Retrieves and combines the fees for the given cart items.
	 *
	 * @since TBD
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The combined fees.
	 */
	protected function get_combined_fees_for_items( array $items ): array {
		if ( empty( $items ) ) {
			return [];
		}

		// Generate a cache key based on the items.
		$cache_key = 'combined_fees_' . md5( wp_json_encode( $items ) );

		// Check if the combined fees are already cached.
		$cached_fees = wp_cache_get( $cache_key, 'combined_fees' );
		if ( false !== $cached_fees ) {
			return $cached_fees;
		}

		// Extract ticket IDs from the items.
		$ticket_ids = array_map(
			function ( $item ) {
				return $item['ticket_id'];
			},
			$items
		);

		// Fetch related ticket fees and automatic fees.
		$related_ticket_fees = $this->order_modifiers_repository->find_relationship_by_post_ids( $ticket_ids, $this->modifier_type );
		$automatic_fees      = $this->order_modifiers_repository->get_all_automatic_fees();

		// Combine the fees and remove duplicates.
		$combined_fees = $this->extract_and_combine_fees( $related_ticket_fees, $automatic_fees );

		// Cache the combined fees for future use.
		wp_cache_set( $cache_key, $combined_fees, 'combined_fees', 120 );

		return $combined_fees;
	}

	/**
	 * Extracts and combines fees from related ticket fees and automatic fees, removing duplicates.
	 *
	 * @since TBD
	 *
	 * @param array $related_ticket_fees The related ticket fees.
	 * @param array $automatic_fees The automatic fees.
	 *
	 * @return array The combined array of fees.
	 */
	protected function extract_and_combine_fees( array $related_ticket_fees, array $automatic_fees ): array {
		$all_fees = array_merge( $related_ticket_fees, $automatic_fees );

		$unique_fees = [];
		foreach ( $all_fees as $fee ) {
			$id = $fee->id;

			if ( ! isset( $unique_fees[ $id ] ) ) {
				$unique_fees[ $id ] = [
					'id'           => $id,
					'raw_amount'   => $fee->raw_amount ?? 0,
					'display_name' => $fee->display_name,
					'sub_type'     => $fee->sub_type,
				];

				// Use the stored subtotal in the fee calculation.
				$unique_fees[ $id ]['fee_amount'] = $this->manager->apply_fees_to_item( $this->subtotal, $unique_fees[ $id ] );
			}
		}

		return array_values( $unique_fees );
	}

	/**
	 * Adds fees as separate items in the cart.
	 *
	 * @since TBD
	 *
	 * @param array $items    The current items in the cart.
	 * @param Value $subtotal The calculated subtotal of the cart items.
	 *
	 * @return array Updated list of items with fees added.
	 */
	protected function append_fees_to_cart( array $items, Value $subtotal ) {
		if ( self::$fees_appended ) {
			return $items;
		}

		if ( empty( $items ) ) {
			return $items;
		}

		$this->subtotal = $subtotal;

		// Get all the combined fees for the items in the cart.
		$fees = $this->get_combined_fees_for_items( $items );

		// Track the fee_ids that have already been added to prevent duplication.
		$existing_fee_ids = [];

		foreach ( $items as $item ) {
			if ( isset( $item['fee_id'] ) ) {
				$existing_fee_ids[ $item['fee_id'] ] = 1;
			}
		}

		// Loop through each fee and append it to the $items array if it's not already added.
		foreach ( $fees as $fee ) {
			// Skip if this fee has already been added to the cart.
			if ( array_key_exists( $fee['id'], $existing_fee_ids ) ) {
				continue;
			}

			// Append the fee to the cart.
			// @todo - Review what needs to be sent. Some of these are used so wp_pluck doesn't cause a warning.
			$items[] = [
				'id'           => "fee_{$fee['id']}",
				'price'        => $fee['fee_amount'],
				'sub_total'    => $fee['fee_amount'],
				'type'         => 'fee',
				'fee_id'       => $fee['id'],
				'display_name' => $fee['display_name'],
				'ticket_id'    => '0',
				'event_id'     => '0',
			];

			// Add the fee ID to the tracking array.
			$existing_fee_ids[ $fee['id'] ] = 1;
		}

		self::$fees_appended = true;

		return $items;
	}
}

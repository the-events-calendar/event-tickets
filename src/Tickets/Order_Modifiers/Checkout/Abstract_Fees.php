<?php
/**
 * Fees Class for handling fee logic in the checkout process.
 *
 * This class is responsible for managing fees, calculating them, and appending them
 * to the cart. It integrates with various filters and hooks during the checkout process.
 *
 * @since TBD
 * @package TEC\Tickets\Order_Modifiers\Checkout
 */

namespace TEC\Tickets\Order_Modifiers\Checkout;

use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Order_Modifiers\Controller;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers_Factory;
use Tribe__Template;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic in the checkout process.
 *
 * @since TBD
 */
abstract class Abstract_Fees {

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
	 * @var Order_Modifiers
	 */
	protected Order_Modifiers $order_modifiers_repository;

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
	 * Tracks whether the fees have already been displayed during the checkout process.
	 *
	 * This static property ensures that the fees are only displayed once across multiple instances
	 * of the class. If set to `true`, the hook for displaying fees will not be added again.
	 * The default is `false`, indicating the fees have not yet been displayed.
	 *
	 * @since TBD
	 * @var bool
	 */
	protected static bool $fees_displayed = false;

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
		$this->order_modifiers_repository              = Order_Modifiers_Factory::get_repository_for_type( $this->modifier_type );
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
	public function calculate_fees( array $values, array $items, Value $subtotal ): array {
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
	 * @param WP_Post         $post     The current post object.
	 * @param array           $items    The items in the cart.
	 * @param Tribe__Template $template The template object for rendering.
	 */
	public function display_fee_section( WP_Post $post, array $items, Tribe__Template $template ): void {
		if ( self::$fees_displayed ) {
			return;
		}
		self::$fees_displayed = true;
		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// Use the stored subtotal for fee calculations.
		$sum_of_fees = $this->manager->calculate_total_fees( $this->subtotal, $combined_fees )->get_decimal();

		// Convert each fee_amount to an integer using get_integer().
		$combined_fees = array_map(
			function ( $fee ) {
				if ( isset( $fee['fee_amount'] ) && $fee['fee_amount'] instanceof Value ) {
					$fee['fee_amount'] = $fee['fee_amount']->get_currency();
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
		$ticket_ids = array_map(
			function ( $item ) {
				return $item['ticket_id'];
			},
			$items
		);

		// Fetch related ticket fees and automatic fees.
		$related_ticket_fees = $this->order_modifiers_repository->find_relationship_by_post_ids( $ticket_ids, $this->modifier_type );
		$automatic_fees      = $this->order_modifiers_repository->find_by_modifier_type_and_meta(
			'fee_applied_to',
			[ 'per', 'all' ],
			'fee_applied_to',
			'all'
		);

		// Combine the fees and remove duplicates.
		return $this->extract_and_combine_fees( $related_ticket_fees, $automatic_fees );
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
					'id'               => $id,
					'raw_amount' => $fee->raw_amount,
					'display_name'     => $fee->display_name,
					'sub_type'         => $fee->sub_type,
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
	 * @param array             $items The current items in the cart.
	 * @param Value             $subtotal The calculated subtotal of the cart items.
	 * @param Gateway_Interface $gateway The payment gateway.
	 * @param array|null        $purchaser Information about the purchaser.
	 *
	 * @return array Updated list of items with fees added.
	 */
	public function append_fees_to_cart( array $items, Value $subtotal, Gateway_Interface $gateway, ?array $purchaser ) {
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
				$existing_fee_ids[] = $item['fee_id']; // Collect existing fee_ids.
			}
		}

		// Loop through each fee and append it to the $items array if it's not already added.
		foreach ( $fees as $fee ) {
			// Skip if this fee has already been added to the cart.
			if ( in_array( $fee['id'], $existing_fee_ids, true ) ) {
				continue; // Skip if fee is already in the cart.
			}

			// Append the fee to the cart.
			$items[] = [
				'id' => 'id for the item', // Default, if type is missing then default to ticket. If it's a ticket and there is no ID, see if 'ticket_id' exists.
				'price'        => $fee['fee_amount'],
				'sub_total'    => $fee['fee_amount'],
				'type'         => 'fee',
				'fee_id'       => $fee['id'],
				'display_name' => $fee['display_name'],
				'ticket_id'    => '', // @todo redscar - Passing this so places where wp_list_luck is used doesn't fail.
				'event_id'     => '',
				'parent' => [
					'id' =>123,
					'type'=>'ticket',
				]
			];

			// Add the fee ID to the tracking array.
			$existing_fee_ids[] = $fee['id'];
		}
		self::$fees_appended = true;
		return $items;
	}
}

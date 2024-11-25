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

use TEC\Common\Contracts\Container;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as Fee_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value;
use Tribe__Template as Template;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use WP_Post;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Fees
 *
 * Handles fees logic in the checkout process.
 *
 * @since TBD
 */
abstract class Abstract_Fees extends Controller_Contract {

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
	 * @var null|Value
	 */
	protected ?Value $subtotal = null;

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
	 *
	 * @since TBD
	 *
	 * @param Container                   $container The DI container.
	 * @param Controller                  $controller The order modifiers controller.
	 * @param Fee_Repository              $fee_repository The repository for interacting with the order modifiers.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The repository for interacting with the order modifiers relationships.
	 * @param Modifier_Manager            $manager The manager for handling modifier calculations and logic.
	 */
	public function __construct( Container $container, Controller $controller, Fee_Repository $fee_repository, Order_Modifier_Relationship $order_modifier_relationship, Modifier_Manager $manager ) {
		parent::__construct( $container );
		$this->modifier_strategy                       = $controller->get_modifier( $this->modifier_type );
		$this->manager                                 = $manager;
		$this->order_modifiers_repository              = $fee_repository;
		$this->order_modifiers_relationship_repository = $order_modifier_relationship;
	}

	/**
	 * Un-registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since TBD
	 */
	abstract public function unregister(): void;

	/**
	 * Registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since TBD
	 */
	abstract public function do_register(): void;

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
		$sum_of_fees = $this->manager->calculate_total_fees( $combined_fees );

		// Add the calculated fees to the total value.
		$values[] = $sum_of_fees;

		return $values;
	}

	/**
	 * Displays the fee section in the checkout.
	 *
	 * @since TBD
	 *
	 * @param WP_Post  $post     The post object for the current event.
	 * @param array    $items    The items in the cart.
	 * @param Template $template The template object for rendering.
	 */
	public function display_fee_section( WP_Post $post, array $items, Template $template ): void {
		if ( ! $this->subtotal ) {
			return;
		}

		// Fetch the combined fees for the items in the cart.
		$combined_fees = $this->get_combined_fees_for_items( $items );

		// Use the stored subtotal for fee calculations.
		$sum_of_fees = $this->manager->calculate_total_fees( $combined_fees )->get_decimal();

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

		if ( empty( $combined_fees ) ) {
			return;
		}

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
	 * @param array $items    The items in the cart.
	 * @param bool  $per_item Whether to return calculated fees per item or all together in a single dimension array.
	 *
	 * @return array The combined fees.
	 */
	protected function get_combined_fees_for_items( array $items, bool $per_item = false ): array {
		if ( empty( $items ) ) {
			return [];
		}

		// Generate a cache key based on the items.
		$cache_key = 'combined_fees_' . md5( wp_json_encode( $items ) );

		$tribe_cache = tribe_cache();

		// Check if the combined fees are already cached.
		if ( isset( $tribe_cache[ $cache_key ] ) && is_array( $tribe_cache[ $cache_key ] ) ) {
			return $per_item ? $tribe_cache[ $cache_key ] : $this->combine_fees( $tribe_cache[ $cache_key ] );
		}

		$automatic_fees = $this->order_modifiers_repository->get_all_automatic_fees();

		$fees_per_item = [];
		foreach ( $items as $item ) {
			if ( ! isset( $item['ticket_id'] ) ) {
				continue;
			}

			$ticket_fees = $this->order_modifiers_repository->find_relationship_by_post_ids( [ $item['ticket_id'] ], $this->modifier_type );

			$ticket_object = Tickets::load_ticket_object( $item['ticket_id'] );

			if ( ! $ticket_object ) {
				continue;
			}

			$fees_per_item[ $item['ticket_id'] ] = [
				'fees'  => $this->extract_and_combine_fees( $ticket_fees, $automatic_fees, new Value( $ticket_object->price ) ),
				'times' => $item['quantity'] ?? 1,
			];
		}

		// Cache the combined fees for future use.
		$tribe_cache[ $cache_key ] = $fees_per_item;

		if ( $per_item ) {
			return $fees_per_item;
		}

		return $this->combine_fees( $fees_per_item );
	}

	/**
	 * Combines the fees for each item in the cart.
	 *
	 * @since TBD
	 *
	 * @param array $fees_per_item The fees per item.
	 *
	 * @return array The combined fees.
	 */
	protected function combine_fees( array $fees_per_item ): array {
		$combined_fees = [];
		foreach ( $fees_per_item as $item_fees ) {
			foreach ( range( 1, $item_fees['times'] ) as $i ) {
				$combined_fees = array_merge( $combined_fees, $item_fees['fees'] );
			}
		}

		return $combined_fees;
	}

	/**
	 * Extracts and combines fees from related ticket fees and automatic fees, removing duplicates.
	 *
	 * @since TBD
	 *
	 * @param array $related_ticket_fees The related ticket fees.
	 * @param array $automatic_fees The automatic fees.
	 * @param Value $ticket_base_price The base price of the ticket.
	 *
	 * @return array The combined array of fees.
	 */
	protected function extract_and_combine_fees( array $related_ticket_fees, array $automatic_fees, Value $ticket_base_price ): array {
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
				$unique_fees[ $id ]['fee_amount'] = $this->manager->apply_fees_to_item( $ticket_base_price, $unique_fees[ $id ] );
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
	public function append_fees_to_cart( array $items, Value $subtotal ) {
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
				'price'        => $fee['fee_amount']->get(),
				'sub_total'    => $fee['fee_amount']->get(),
				'type'         => 'fee',
				'fee_id'       => $fee['id'],
				'display_name' => $fee['display_name'],
				'ticket_id'    => '0',
				'event_id'     => '0',
				'quantity'     => 1,
			];

			// Add the fee ID to the tracking array.
			$existing_fee_ids[ $fee['id'] ] = 1;
		}

		self::$fees_appended = true;

		return $items;
	}

	/**
	 * Resets the fees state and recalculates the subtotal to zero.
	 *
	 * This method clears the flag indicating that fees have been appended
	 * and resets the subtotal to its default value of zero.
	 *
	 * @return void
	 */
	public function reset_fees_and_subtotal(): void {
		self::$fees_appended = false;
		$this->subtotal      = null;
	}
}

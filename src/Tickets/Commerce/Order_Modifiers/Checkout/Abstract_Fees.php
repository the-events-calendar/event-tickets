<?php
/**
 * Fees Class for handling fee logic in the checkout process.
 *
 * This class is responsible for managing fees, calculating them, and appending them
 * to the cart. It integrates with various filters and hooks during the checkout process.
 *
 * @since   5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers\Checkout
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee_Modifier_Manager as Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as Fee_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Integer_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Legacy_Value_Factory;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Template as Template;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Fees
 *
 * Handles fees logic in the checkout process.
 *
 * @since 5.18.0
 */
abstract class Abstract_Fees extends Controller_Contract {

	use Valid_Types;

	/**
	 * The modifier type used for fees.
	 *
	 * @since 5.18.0
	 * @var string
	 */
	protected string $modifier_type = 'fee';

	/**
	 * The modifier strategy for applying fees.
	 *
	 * @since 5.18.0
	 * @var Modifier_Strategy_Interface
	 */
	protected Modifier_Strategy_Interface $modifier_strategy;

	/**
	 * Manager for handling modifier calculations and logic.
	 *
	 * @since 5.18.0
	 * @var Modifier_Manager
	 */
	protected Modifier_Manager $manager;

	/**
	 * Repository for accessing order modifiers.
	 *
	 * @since 5.18.0
	 * @var Fee_Repository
	 */
	protected Fee_Repository $order_modifiers_repository;

	/**
	 * Repository for accessing and managing relationships between order modifiers.
	 *
	 * @since 5.18.0
	 * @var Order_Modifier_Relationship
	 */
	protected Order_Modifier_Relationship $order_modifiers_relationship_repository;

	/**
	 * Subtotal value used in fee calculations.
	 *
	 * This represents the total amount used as a basis for calculating applicable fees.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
	 * @var bool
	 */
	protected static bool $fees_appended = false;

	/**
	 * The order modifiers controller.
	 *
	 * @since 5.19.1
	 * @var Controller
	 */
	protected Controller $controller;

	/**
	 * Constructor
	 *
	 * @since 5.18.0
	 *
	 * @param Container                   $container                   The DI container.
	 * @param Controller                  $controller                  The order modifiers controller.
	 * @param Fee_Repository              $fee_repository              The repository for interacting with the order
	 *                                                                 modifiers.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The repository for interacting with the order
	 *                                                                 modifiers relationships.
	 * @param Modifier_Manager            $manager                     The manager for handling modifier calculations
	 *                                                                 and logic.
	 */
	public function __construct(
		Container $container,
		Controller $controller,
		Fee_Repository $fee_repository,
		Order_Modifier_Relationship $order_modifier_relationship,
		Modifier_Manager $manager
	) {
		parent::__construct( $container );
		$this->controller                              = $controller;
		$this->manager                                 = $manager;
		$this->order_modifiers_repository              = $fee_repository;
		$this->order_modifiers_relationship_repository = $order_modifier_relationship;

		add_action( 'init', [ $this, 'set_modifier_strategy' ] );
	}

	/**
	 * Sets the modifier strategy for applying fees.
	 *
	 * @since 5.19.1
	 */
	public function set_modifier_strategy() {
		$this->modifier_strategy = $this->controller->get_modifier( $this->modifier_type );
	}

	/**
	 * Un-registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since 5.18.0
	 */
	abstract public function unregister(): void;

	/**
	 * Registers the necessary hooks for adding fees to the checkout process.
	 *
	 * @since 5.18.0
	 */
	abstract public function do_register(): void;

	/**
	 * Calculates the fees and modifies the total value in the checkout process.
	 *
	 * @since 5.18.0
	 *
	 * @param array $values   The existing values being passed through the filter.
	 * @param array $items    The items in the cart.
	 * @param Value $subtotal The list of subtotals from the items.
	 *
	 * @return array The updated total values, including the fees.
	 */
	public function calculate_fees( array $values, array $items, Value $subtotal ): array {
		$cache_key = 'calculate_fees_' . md5( wp_json_encode( $items ) );
		$cache     = tribe_cache();

		if ( ! empty( $cache[ $cache_key ] ) && is_array( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

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

		// Calculate the total fees based on each individual fee.
		$sum_of_fees = $this->manager->calculate_total_fees( $combined_fees );

		// Add the calculated fees to the total value.
		$values[] = $sum_of_fees;

		$cache[ $cache_key ] = $values;

		return $cache[ $cache_key ];
	}

	/**
	 * Displays the fee section in the checkout.
	 *
	 * @since 5.18.0
	 *
	 * @param WP_Post  $post     The post object for the current event.
	 * @param array    $items    The items in the cart.
	 * @param Template $template The template object for rendering.
	 */
	public function display_fee_section( WP_Post $post, array $items, Template $template ): void {
		if ( ! $this->subtotal ) {
			return;
		}

		// Process the fees for each item into a single array.
		$combined_fees = $this->prepare_fees_for_frontend_display( $items );

		// Return early if there are no fees to display.
		if ( empty( $combined_fees ) ) {
			return;
		}

		// Use the stored subtotal for fee calculations.
		$subtotals   = array_values( wp_list_pluck( $combined_fees, 'subtotal' ) );
		$total       = Currency_Value::sum( ...$subtotals );
		$sum_of_fees = Legacy_Value_Factory::to_legacy_value( $total->get_raw_value() )->get_decimal();

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
	 * @since 5.18.0
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

			$ticket_fees = $this->order_modifiers_repository->find_relationship_by_post_ids(
				[ $item['ticket_id'] ],
				$this->modifier_type
			);

			$ticket_object = Tickets::load_ticket_object( $item['ticket_id'] );

			if ( ! $ticket_object ) {
				continue;
			}

			$fees_per_item[ $item['ticket_id'] ] = [
				'fees'  => $this->extract_and_combine_fees(
					$ticket_fees,
					$automatic_fees,
					new Value( $ticket_object->price )
				),
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
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @param array $related_ticket_fees The related ticket fees.
	 * @param array $automatic_fees      The automatic fees.
	 * @param Value $ticket_base_price   The base price of the ticket.
	 *
	 * @return array The combined array of fees.
	 */
	protected function extract_and_combine_fees(
		array $related_ticket_fees,
		array $automatic_fees,
		Value $ticket_base_price
	): array {
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
				$unique_fees[ $id ]['fee_amount'] = $this->manager->apply_fees_to_item(
					$ticket_base_price,
					$unique_fees[ $id ]
				);
			}
		}

		return array_values( $unique_fees );
	}

	/**
	 * Adds fees as separate items in the cart.
	 *
	 * @since 5.18.0
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
		$raw_fees = $this->get_combined_fees_for_items( $items, true );

		// Set up the array of fee items.
		$fee_items = [];
		foreach ( $raw_fees as $item_id => $fee_data ) {
			$quantity = $fee_data['times'] ?? 1;

			foreach ( $fee_data['fees'] as $fee ) {
				/** @var Precision_Value $amount */
				$amount = $fee['fee_amount'];

				// If the fee exists, update the quantity and recalculate the subtotal.
				if ( array_key_exists( $fee['id'], $fee_items ) ) {
					// Set the new quantity.
					$total_quantity = $quantity + $fee_items[ $fee['id'] ]['quantity'];

					// Update the quantity and recalculate the subtotal.
					$fee_items[ $fee['id'] ]['quantity'] = $total_quantity;

					// DO NOT REMOVE the `+=` operator here. It is necessary for the calculation.
					$fee_items[ $fee['id'] ]['sub_total'] += $amount->multiply_by_integer(
						new Integer_Value( $quantity )
					)->get();

					continue;
				}

				$fee_items[ $fee['id'] ] = [
					'id'           => "fee_{$fee['id']}_{$item_id}",
					'type'         => 'fee',
					'price'        => $amount->get(),
					'sub_total'    => $amount->multiply_by_integer( new Integer_Value( $quantity ) )->get(),
					'fee_id'       => $fee['id'],
					'display_name' => $fee['display_name'],
					'ticket_id'    => $item_id,
					'event_id'     => '0',
					'quantity'     => $quantity,
				];
			}
		}

		// Add the fee items to the other cart items.
		$items = array_merge( $items, $fee_items );

		self::$fees_appended = true;

		return $items;
	}

	/**
	 * Prepares the fees for display in the frontend.
	 *
	 * @since 5.18.0
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The fees prepared for display.
	 */
	protected function prepare_fees_for_frontend_display( array $items ): array {
		// Get the combined fees for the items in the cart.
		$fees_by_item = $this->get_combined_fees_for_items( $items, true );

		// Combine the fees for display.
		$combined_fees = [];
		foreach ( $fees_by_item as $item_id => $fee_data ) {
			$quantity = $fee_data['times'] ?? 1;

			foreach ( $fee_data['fees'] as $fee ) {
				$amount  = Currency_Value::create( $fee['fee_amount'] );
				$subtype = $fee['sub_type'];
				$id      = $fee['id'];
				$name    = $fee['display_name'];

				// Skip fees that are not a valid subtype.
				if ( ! $this->is_valid_subtype( $subtype ) ) {
					continue;
				}

				$subtotal = $quantity > 1
					? $amount->multiply_by_integer( new Integer_Value( $quantity ) )
					: $amount;

				/*
				 * Because of how the items are grouped, we need to combine the fees differentlyl
				 * based on whether they are flat or a percentage. Flat fees will be the same price
				 * regardless of what item they are attached to. Percent fees will be calculated
				 * based on the price of the item they are attached to. Therefore, we need to index
				 * the percentage fees using the fee ID and the item ID.
				 */

				$index = 'percent' === $subtype
					? "{$fee['id']}-{$item_id}"
					: $fee['id'];

				if ( array_key_exists( $index, $combined_fees ) ) {
					// Merge the important parts of the fee.
					$existing_fee              = $combined_fees[ $index ];
					$existing_fee['quantity'] += $quantity;
					$existing_fee['subtotal']  = $existing_fee['subtotal']->add( $subtotal );
					$existing_fee['for_items'] = array_unique(
						array_merge( $existing_fee['for_items'], [ $item_id ] )
					);

					// Update the combined fee.
					$combined_fees[ $index ] = $existing_fee;
				} else {
					$combined_fees[ $index ] = [
						'id'           => $id,
						'display_name' => $name,
						'quantity'     => $quantity,
						'subtotal'     => $subtotal,
						'for_items'    => [ $item_id ],
					];
				}
			}
		}

		// Sort the array alphabetically by display name.
		usort(
			$combined_fees,
			static function ( $a, $b ) {
				return strcasecmp( $a['display_name'], $b['display_name'] );
			}
		);

		return $combined_fees;
	}

	/**
	 * Resets the fees state and resets the subtotal to zero.
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

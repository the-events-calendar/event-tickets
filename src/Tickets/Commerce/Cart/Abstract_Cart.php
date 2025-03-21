<?php
/**
 * Abstract Cart
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Cart
 */

namespace TEC\Tickets\Commerce\Cart;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory as Factory;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Abstract_Cart
 *
 * @since 5.10.0
 *
 * @property float $cart_total [Deprecated] The calculated cart total.
 */
abstract class Abstract_Cart implements Cart_Interface {

	use Cart_Trait;

	/**
	 * Cart subtotal.
	 *
	 * This should be the total of items in the cart without any additional calculations.
	 *
	 * @since 5.21.0
	 *
	 * @var Precision_Value
	 */
	protected Precision_Value $cart_subtotal;

	/**
	 * Cart total.
	 *
	 * This should be the total that will be paid by the customer after all calculations
	 * have been done.
	 *
	 * @since 5.10.0
	 * @since 5.21.0 Marked the property as protected, and changed to a Precision_Value.
	 *
	 * @var Precision_Value
	 */
	protected Precision_Value $cart_total;

	/**
	 * @var string The Cart hash for this cart.
	 */
	protected $cart_hash;

	/**
	 * Array of items with full parameters available.
	 *
	 * @since 5.21.0
	 *
	 * @var array
	 */
	protected array $full_param_items = [];

	/**
	 * Whether the items in the cart have full parameters.
	 *
	 * @since 5.21.0
	 *
	 * @var bool
	 */
	protected bool $items_have_full_params = false;

	/**
	 * Whether the cart subtotal has been calculated.
	 *
	 * @since 5.21.0
	 *
	 * @var bool
	 */
	protected bool $subtotal_calculated = false;

	/**
	 * Whether the cart total has been calculated.
	 *
	 * @since 5.21.0
	 *
	 * @var bool
	 */
	protected bool $total_calculated = false;

	/**
	 * Abstract_Cart constructor.
	 *
	 * @since 5.21.0
	 */
	public function __construct() {
		$this->cart_subtotal = new Precision_Value( 0.0 );
		$this->cart_total    = new Precision_Value( 0.0 );
	}

	/**
	 * Determines if this instance of the cart has a public page.
	 *
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function has_public_page() {
		return false;
	}

	/**
	 * Gets the Cart mode based.
	 *
	 * @since 5.21.0
	 *
	 * @return string
	 */
	public function get_mode() {
		return Cart::REDIRECT_MODE;
	}

	/**
	 * Get the tickets currently in the cart for a given provider.
	 *
	 * @since 5.10.0
	 * @since 5.21.0 Added the $type parameter.
	 *
	 * @param bool   $full_item_params Determines all the item params, including event_id, sub_total, and obj.
	 * @param string $type             The type of item to get from the cart. Default is 'ticket'. Use 'all' to get all items.
	 *
	 * @return array<string, mixed> List of items.
	 */
	public function get_items_in_cart( $full_item_params = false, string $type = 'ticket' ): array {
		$items = $this->filter_items_by_type( $type );

		// When Items is empty in any capacity return an empty array.
		if ( empty( $items ) ) {
			return [];
		}

		if ( $full_item_params ) {
			$items = $this->add_full_item_params( $items );
		}

		return array_filter( $items );
	}

	/**
	 * Get the total value of the cart, including additional values such as fees or discounts.
	 *
	 * This method calculates the total by first computing the subtotal from all items in the cart,
	 * and then applying any additional values (e.g., fees or discounts) provided via the
	 * `tec_tickets_commerce_get_cart_additional_values` filter.
	 *
	 * @since 5.10.0
	 * @since 5.18.0 Refactored logic, to include a new filter.
	 * @since 5.21.0 Added internal caching for this method to prevent duplicate calculations.
	 *
	 * @return float The total value of the cart.
	 */
	public function get_cart_total(): float {
		// If the total has already been calculated, return it.
		if ( $this->total_calculated ) {
			return $this->cart_total->get();
		}

		$subtotal  = new Precision_Value( $this->get_cart_subtotal() );
		$all_items = $this->get_items_in_cart( true, 'all' );

		/**
		 * Filters the additional values in the cart in order to add additional fees or discounts.
		 *
		 * Additional values must be instances of the `Precision_Value` class to ensure consistent behavior.
		 *
		 * @since 5.21.0
		 *
		 * @param Precision_Value[] $values   An array of `Precision_Value` instances representing additional fees or discounts.
		 * @param array             $items    The items currently in the cart.
		 * @param Precision_Value   $subtotal The total of the subtotals from the items.
		 *
		 * @var Precision_Value[] $additional_values
		 */
		$additional_values = apply_filters(
			'tec_tickets_commerce_get_cart_additional_values_total',
			[],
			$all_items,
			$subtotal
		);

		// Set up the new subtotal that includes the additional values.
		$subtotal = Precision_Value::sum( $subtotal, ...$additional_values );

		// If the subtotal is zero or less, return the subtotal without further calculations.
		if ( $subtotal->get() <= 0.0 ) {
			$this->total_calculated = true;
			return $this->cart_total->get();
		}

		$callable_subtotals = [];

		// Get the items that have a dynamic subtotal.
		$callable_items = array_filter(
			$all_items,
			static fn( $item ) => is_callable( $item['sub_total'] )
		);

		// Calculate the items that are dynamic. These items are not included in the subtotal calculation.
		$callable_items = $this->update_items_with_subtotal( $callable_items, $subtotal->get() );

		// Get the subtotals for the callable items as Precision_Value objects.
		foreach ( $callable_items as $item ) {
			$callable_subtotals[] = Factory::to_precision_value( $item['sub_total'] );
		}

		// Calculate the new value from all of the subtotals.
		$total = Precision_Value::sum(
			$subtotal,
			...$callable_subtotals
		);

		// Only update the stored total if it's greater than zero.
		$this->cart_total = $total->get() > 0.0
			? $total
			: new Precision_Value( 0.0 );

		// Mark that the total has been calculated.
		$this->total_calculated = true;

		return $this->cart_total->get();
	}

	/**
	 * Get the subtotal of the cart items.
	 *
	 * The subtotal is the sum of all item subtotals without additional values like fees or discounts.
	 *
	 * @since 5.18.0 Refactored to avoid cumulative calculations.
	 * @since 5.21.0 Added internal caching for this method to prevent duplicate calculations.
	 *
	 * @return float The subtotal of the cart.
	 */
	public function get_cart_subtotal(): float {
		// If the subtotal has already been calculated, return it.
		if ( $this->subtotal_calculated ) {
			return $this->cart_subtotal->get();
		}

		/** @var Precision_Value[] $subtotals The subtotal objects. */
		$subtotals = [];

		// Calculate the total from the subtotals of each item.
		$all_items = $this->get_items_in_cart( true, 'all' );

		// Process any items that have the subtotal as a simple float.
		$float_items = array_filter( $all_items, static fn( $item ) => is_float( $item['sub_total'] ) );
		foreach ( $float_items as $item ) {
			$subtotals[] = new Precision_Value( $item['sub_total'] );
		}

		// Process any items that have the subtotal as a Value object.
		$value_items = array_filter( $all_items, static fn( $item ) => $item['sub_total'] instanceof Value );
		foreach ( $value_items as $item ) {
			$subtotals[] = Factory::to_precision_value( $item['sub_total'] );
		}

		$subtotal = Precision_Value::sum( ...$subtotals );

		/**
		 * Filters the additional values in the cart in order to add additional fees or discounts.
		 *
		 * Additional values must be instances of the `Precision_Value` class to ensure consistent behavior.
		 *
		 * @since 5.21.0
		 *
		 * @param Precision_Value[] $values         An array of `Precision_Value` instances representing additional fees or discounts.
		 * @param array             $items          The items currently in the cart.
		 * @param Precision_Value   $subtotal_value The total of the subtotals from the items.
		 *
		 * @var Precision_Value[] $additional_values
		 */
		$additional_values = apply_filters(
			'tec_tickets_commerce_get_cart_additional_values_subtotal',
			[],
			$all_items,
			$subtotal
		);

		$this->cart_subtotal = Precision_Value::sum( $subtotal, ...$additional_values );

		// Set the subtotal as calculated.
		$this->subtotal_calculated = true;

		return $this->cart_subtotal->get();
	}

	/**
	 * Sets the cart hash.
	 *
	 * @since 5.1.9
	 * @since 5.2.0 Renamed to set_hash instead of set_id
	 *
	 * @param string $hash The hash to set.
	 */
	public function set_hash( $hash ) {
		/**
		 * Filters the cart setting of a hash used for the Cart.
		 *
		 * @since 5.2.0
		 *
		 * @param string         $cart_hash Cart hash value.
		 * @param Cart_Interface $cart      Which cart object we are using here.
		 */
		$this->cart_hash = (string) apply_filters( 'tec_tickets_commerce_cart_set_hash', $hash, $this );
	}

	/**
	 * Gets the cart hash.
	 *
	 * @since 5.2.0
	 *
	 * @return string The hash.
	 */
	public function get_hash() {
		/**
		 * Filters the cart hash used for the Cart.
		 *
		 * @since 5.2.0
		 *
		 * @param string         $cart_hash Cart hash value.
		 * @param Cart_Interface $cart      Which cart object we are using here.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_cart_get_hash', $this->cart_hash, $this );
	}

	/**
	 * Add the full set of parameters to the items in the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The items in the cart with the full set of parameters.
	 */
	protected function add_full_item_params( array $items ): array {
		if ( $this->items_have_full_params ) {
			return $this->full_param_items;
		}

		$this->full_param_items = array_map(
			static function ( $item ) {
				// Try to get the ticket object, and if it's not valid, remove it from the cart.
				$item['obj'] = Tickets::load_ticket_object( $item['ticket_id'] );
				if ( ! $item['obj'] instanceof Ticket_Object ) {
					return null;
				}

				$sub_total_value = Value::create();
				$sub_total_value->set_value( $item['obj']->price );

				$item['event_id']  = $item['obj']->get_event_id();
				$item['sub_total'] = $sub_total_value->sub_total( $item['quantity'] );
				$item['type']      = 'ticket';

				return $item;
			},
			$items
		);

		$this->items_have_full_params = true;

		return $this->full_param_items;
	}

	/**
	 * Prepare the data for cart processing.
	 *
	 * This method should be used to do any pre-processing of the data before
	 * it is passed to the process() method. If no pre-processing is needed,
	 * this method should return the data as is.
	 *
	 * @since 5.1.10
	 * @since 5.21.0 Moved to the abstract class.
	 *
	 * @param array $data To be processed by the cart.
	 *
	 * @return array
	 */
	public function prepare_data( array $data = [] ) {
		/**
		 * Filter the data before it is processed by the cart.
		 *
		 * @since 5.21.0
		 *
		 * @param array          $data The data to be processed by the cart.
		 * @param Cart_Interface $this The cart object.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_cart_repo_prepare_data', $data, $this );
	}

	/**
	 * Get a non-public property.
	 *
	 * @since 5.21.0
	 *
	 * @param string $property The property to get.
	 *
	 * @return mixed The property value.
	 * @throws InvalidArgumentException If the property is not meant to be accessed.
	 */
	public function __get( $property ) {
		switch ( $property ) {
			case 'cart_total':
				_doing_it_wrong(
					sprintf( '%s::%s', __CLASS__, esc_html( $property ) ),
					sprintf(
						/* translators: %s: property name */
						esc_html__( 'Accessing the %s property directly is deprecated.', 'event-tickets' ),
						esc_html( $property )
					),
					'5.21.0'
				);
				return $this->cart_total;

			default:
				throw new InvalidArgumentException( sprintf( 'Invalid property: %s', $property ) );
		}
	}

	/**
	 * Get items in the cart of a particular type.
	 *
	 * @since 5.21.0
	 *
	 * @param string $type  The type of item to get from the item array. Use 'all' to get all items.
	 * @param ?array $items The items to filter. If omitted, items will be retrieved from the cart.
	 *
	 * @return array The filtered items.
	 */
	protected function filter_items_by_type( string $type, ?array $items = null ): array {
		// Get the items from the cart if they aren't provided.
		if ( null === $items ) {
			$items = $this->get_items();
		}

		// If the type is 'all', then no filtering is needed.
		if ( 'all' === $type ) {
			return $items;
		}

		// Filter the items if we have something other than 'all' as the type.
		return array_filter(
			$items,
			static function ( $item ) use ( $type ) {
				return $type === ( $item['type'] ?? 'ticket' );
			}
		);
	}

	/**
	 * Get the quantity of an item in the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param int|string $item_id The item ID.
	 *
	 * @return int The quantity of the item in the cart.
	 *
	 * @throws InvalidArgumentException If the item is not in the cart.
	 */
	public function get_item_quantity( $item_id ): int {
		if ( ! $this->has_item( $item_id ) ) {
			throw new InvalidArgumentException( 'Item not found in cart.' );
		}

		return (int) $this->get_items()[ $item_id ]['quantity'];
	}

	/**
	 * Reset the cart calculations.
	 *
	 * After calling this method, calculations will be performed again.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function reset_calculations() {
		$this->items_have_full_params = false;
		$this->subtotal_calculated    = false;
		$this->total_calculated       = false;
	}

	/**
	 * Update dynamic items using a subtotal value.
	 *
	 * This will convert any callable items to a Value object using the given
	 * subtotal as input.
	 *
	 * @since 5.21.0
	 *
	 * @param array  $items    The items to update.
	 * @param ?float $subtotal The subtotal to use for the calculation. If null, the cart subtotal will be used.
	 *
	 * @return array The updated items.
	 */
	public function update_items_with_subtotal( array $items, ?float $subtotal = null ): array {
		$subtotal ??= $this->get_cart_subtotal();
		foreach ( $items as &$item ) {
			if ( ! is_callable( $item['sub_total'] ) ) {
				continue;
			}

			// Get the result and update the item with a value object.
			$result            = $item['sub_total']( $subtotal );
			$item['sub_total'] = Value::create( $result );
		}

		return $items;
	}
}

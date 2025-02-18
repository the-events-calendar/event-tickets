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
	 * @since TBD
	 *
	 * @var ?float
	 */
	protected ?float $cart_subtotal = null;

	/**
	 * Cart total.
	 *
	 * This should be the total that will be paid by the customer after all calculations
	 * have been done.
	 *
	 * @since 5.10.0
	 * @since TBD Marked the property as protected.
	 *
	 * @var ?float
	 */
	protected ?float $cart_total = null;

	/**
	 * @var string The Cart hash for this cart.
	 */
	protected $cart_hash;

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
	 * @since TBD
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
	 * @since TBD Added the $type parameter.
	 *
	 * @param bool   $full_item_params Determines all the item params, including event_id, sub_total, and obj.
	 * @param string $type             The type of item to get from the cart. Default is 'ticket'. Use 'all' to get all items.
	 *
	 * @return array<string, mixed> List of items.
	 */
	public function get_items_in_cart( $full_item_params = false, string $type = 'ticket' ): array {
		$items = $this->get_items_by_type( $type );

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
	 * and then applying any additional values (e.g., fees or discounts) provided via the `tec_tickets_commerce_get_cart_additional_values` filter.
	 *
	 * @since 5.18.0 Refactored logic, to include a new filter.
	 * @since 5.10.0
	 *
	 * @return float The total value of the cart, or null if there are no items.
	 */
	public function get_cart_total() {
		$subtotal = $this->get_cart_subtotal();
		if ( ! $subtotal ) {
			return 0.0;
		}

		$items = $this->get_items_in_cart( true );

		// Extract subtotals from the cart items.
		$sub_totals = array_filter( wp_list_pluck( $items, 'sub_total' ) );

		/**
		 * Filters the additional values in the cart in order to add additional fees or discounts.
		 *
		 * Additional values must be instances of the `Value` class to ensure consistent behavior.
		 *
		 * @since 5.18.0
		 *
		 * @param Value[] $values     An array of `Value` instances representing additional fees or discounts.
		 * @param array   $items      The items currently in the cart.
		 * @param Value   $sub_totals The total of the subtotals from the items.
		 */
		$additional_values = apply_filters(
			'tec_tickets_commerce_get_cart_additional_values',
			[],
			$items,
			Value::create()->total( $sub_totals )
		);

		// Combine the subtotals and additional values.
		$total_value = Value::create()->total( array_merge( $sub_totals, $additional_values ) );

		$this->cart_total = $total_value->get_decimal();

		return $total_value->get_decimal();
	}

	/**
	 * Get the subtotal of the cart items.
	 *
	 * The subtotal is the sum of all item subtotals without additional values like fees or discounts.
	 *
	 * @since 5.18.0 Refactored to avoid cumulative calculations.
	 *
	 * @return float The subtotal of the cart.
	 */
	public function get_cart_subtotal(): float {
		// Reset cart_total to ensure it's not cumulative across calls.
		$this->cart_total = 0.0;

		$items = $this->get_items_in_cart( true );

		// If no items in the cart, return null.
		if ( empty( $items ) ) {
			return 0.0;
		}

		// Calculate the total from the subtotals of each item.
		foreach ( $items as $item ) {
			$this->cart_total += $item['sub_total']->get_decimal();
		}

		return $this->cart_total;
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
		$this->cart_hash = apply_filters( 'tec_tickets_commerce_cart_set_hash', $hash, $this );
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
		return apply_filters( 'tec_tickets_commerce_cart_get_hash', $this->cart_hash, $this );
	}

	/**
	 * Add the full set of parameters to the items in the cart.
	 *
	 * @since TBD
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The items in the cart with the full set of parameters.
	 */
	protected function add_full_item_params( array $items ): array {
		return array_map(
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
	}

	/**
	 * Prepare the data for cart processing.
	 *
	 * This method should be used to do any pre-processing of the data before
	 * it is passed to the process() method. If no pre-processing is needed,
	 * this method should return the data as is.
	 *
	 * @since 5.1.10
	 * @since TBD Moved to the abstract class.
	 *
	 * @param array $data To be processed by the cart.
	 *
	 * @return array
	 */
	public function prepare_data( array $data = [] ) {
		/**
		 * Filter the data before it is processed by the cart.
		 *
		 * @since TBD
		 *
		 * @param array          $data The data to be processed by the cart.
		 * @param Cart_Interface $this The cart object.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_cart_repo_prepare_data', $data, $this );
	}

	/**
	 * Get a non-public property.
	 *
	 * @since TBD
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
					'TBD'
				);
				return $this->cart_total;

			default:
				throw new InvalidArgumentException( sprintf( 'Invalid property: %s', $property ) );
		}
	}

	/**
	 * Get items in the cart of a particular type.
	 *
	 * @since TBD
	 *
	 * @param string $type The type of item to get from the cart. Use 'all' to get all items.
	 *
	 * @return array The items in the cart.
	 */
	protected function get_items_by_type( string $type ): array {
		$items = $this->get_items();

		// Filter the items if we have something other than 'all' as the type.
		if ( 'all' !== $type ) {
			$items = array_filter(
				$items,
				static function ( $item ) use ( $type ) {
					return $type === ( $item['type'] ?? 'ticket' );
				}
			);
		}

		return $items;
	}

	/**
	 * Get the quantity of an item in the cart.
	 *
	 * @since TBD
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
}

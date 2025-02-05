<?php
/**
 * A cart that is agnostic to what types of items it contains.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__REST__V1__Messages as Messages;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class Agnostic_Cart
 *
 * @since TBD
 */
class Agnostic_Cart extends Abstract_Cart {

	use Cart_Trait;

	/**
	 * @var Cart_Item[] The list of items.
	 */
	protected array $items = [];

	/**
	 * Whether the cart subtotal has been calculated.
	 *
	 * @var bool
	 */
	protected bool $subtotal_calculated = false;

	/**
	 * Whether the cart total has been calculated.
	 *
	 * @var bool
	 */
	protected bool $total_calculated = false;

	/**
	 * Gets the cart items from the cart.
	 *
	 * This method should include any persistence by the cart implementation.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_items() {
		if ( ! empty( $this->items ) ) {
			return $this->get_items_plain();
		}

		if ( ! $this->exists() ) {
			return [];
		}

		$items = get_transient( $this->get_transient_key( $this->get_hash() ) );
		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->set_items_plain( $items );
		}

		return $this->get_items_plain();
	}

	/**
	 * Gets the cart items as plain items instead of objects.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_items_plain(): array {
		return array_map(
			fn( $item ) => $item->to_array(),
			$this->items
		);
	}

	/**
	 * Sets the cart items from a plain items array.
	 *
	 * @since TBD
	 *
	 * @param array $items The items to set.
	 */
	protected function set_items_plain( array $items ) {
		$this->items = array_map(
			fn( $item ) => new Cart_Item( $item ),
			$items
		);
		$this->reset_calculations();
	}

	/**
	 * Saves the cart.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 *
	 * @since TBD
	 */
	public function save() {
		$cart_hash = tribe( Cart::class )->get_cart_hash( true );

		if ( false === $cart_hash ) {
			return false;
		}

		$this->set_hash( $cart_hash );

		if ( ! $this->has_items() ) {
			$this->clear();

			return false;
		}

		set_transient(
			$this->get_transient_key( $cart_hash ),
			$this->get_items_plain(),
			$this->get_transient_expiration()
		);

		tribe( Cart::class )->set_cart_hash_cookie( $cart_hash );

		return true;
	}

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 */
	public function clear() {
		$cart_hash = tribe( Cart::class )->get_cart_hash() ?? '';

		if ( false === $cart_hash ) {
			return;
		}

		$this->set_hash( null );
		delete_transient( $this->get_transient_key( $cart_hash ) );
		tribe( Cart::class )->set_cart_hash_cookie( null );

		// Reset items and cart total.
		$this->items         = [];
		$this->cart_subtotal = null;
		$this->cart_total    = null;
		$this->reset_calculations();
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since TBD
	 *
	 * @param array $criteria Additional criteria to use when checking if the cart exists.
	 *
	 * @return bool Whether the cart exists or not.
	 */
	public function exists( array $criteria = [] ) {
		$hash = tribe( Cart::class )->get_cart_hash();
		if ( empty( $hash ) ) {
			return false;
		}

		return (bool) get_transient( $this->get_transient_key( $hash ) );
	}

	/**
	 * Whether the cart contains items or not.
	 *
	 * @since TBD
	 *
	 * @return bool|int The number of products in the cart (regardless of the products quantity) or `false`
	 */
	public function has_items() {
		$count = count( $this->get_items() );

		return $count > 0 ? $count : false;
	}

	/**
	 * Whether an item is in the cart or not.
	 *
	 * @since TBD
	 *
	 * @param string $item_id The item ID.
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id ) {
		if ( empty( $this->items ) ) {
			return false;
		}

		if ( ! array_key_exists( $item_id, $this->items ) ) {
			return false;
		}

		return $this->items[ $item_id ]['quantity'] ?? false;
	}

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since TBD
	 *
	 * @param int|string $item_id    The item ID.
	 * @param int        $quantity   The quantity to remove.
	 * @param ?array     $extra_data Extra data to save to the item.
	 */
	public function add_item( $item_id, $quantity, ?array $extra_data = null ) {
		// Allow for the type of item to be passed in.
		$type = $extra_data['type'] ?? 'ticket';
		unset( $extra_data['type'] );

		// Ensure the quantity is an integer.
		$new_quantity = (int) $quantity;

		// If the item is already in the cart, update the quantity.
		$current_quantity = $this->has_item( $item_id );
		if ( false !== $current_quantity ) {
			$this->update_item( $item_id, $new_quantity, $extra_data );

			return;
		}

		// If the quantity is zero or less, don't add the item.
		if ( $new_quantity <= 0 ) {
			return;
		}

		// Add the item to the array of items.
		$this->items[ $item_id ] = new Cart_Item(
			[
				"{$type}_id" => $item_id,
				'quantity'   => $new_quantity,
				'type'       => $type,
				'extra'      => $extra_data ?? [],
			]
		);

		$this->reset_calculations();
	}

	/**
	 * Update an item in the cart.
	 *
	 * @param string $item_id    The item ID.
	 * @param int    $quantity   The quantity to update.
	 * @param ?array $extra_data Extra data to save to the item.
	 *
	 * @return void
	 */
	protected function update_item( $item_id, int $quantity, ?array $extra_data = null ): void {
		$item_object  = $this->items[ $item_id ];
		$new_quantity = $item_object->add_quantity( $quantity );

		// If the quantity is less than 1, remove the item from the cart.
		if ( $new_quantity < 1 ) {
			$this->remove_item( $item_id );

			return;
		}

		// Maybe update the extra data.
		if ( null !== $extra_data ) {
			$item_object['extra'] = $extra_data;
		}

		$this->reset_calculations();
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @since TBD
	 *
	 * @param int|string $item_id  The item ID.
	 * @param null|int   $quantity The quantity to remove.
	 */
	public function remove_item( $item_id, $quantity = null ) {
		if ( null === $quantity ) {
			unset( $this->items[ $item_id ] );
			$this->reset_calculations();

			return;
		}

		$this->update_item( $item_id, -abs( (int) $quantity ) );
	}

	/**
	 * Process the items in the cart.
	 *
	 * @since TBD
	 *
	 * @param array $data to be processed by the cart.
	 *
	 * @return array|bool
	 */
	public function process( array $data = [] ) {
		if ( empty( $data ) ) {
			return false;
		}

		// Reset the contents of the cart.
		$this->clear();

		/** @var Messages $messages */
		$messages = tribe( 'tickets.rest-v1.messages' );

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Prepare the error message array.
		$errors = [];

		// Natively handle adding tickets as items to the cart.
		foreach ( $data['tickets'] as $ticket ) {
			// Enforces that the min to add is 1.
			$quantity = max( 1, (int) $ticket['quantity'] );

			// Check if the ticket can be added to the cart.
			$can_add_to_cart = $tickets_handler->ticket_has_capacity( $ticket['ticket_id'], $quantity, $ticket['obj'] );

			// Skip and add to the errors if the ticket can't be added to the cart.
			if ( is_wp_error( $can_add_to_cart ) ) {
				$errors[] = $can_add_to_cart;

				continue;
			}

			// Add to / update quantity in cart.
			$this->add_item( $ticket['ticket_id'], $quantity, $ticket['extra'] );
		}

		/**
		 * Fires after the ticket data has been processed.
		 *
		 * This allows for further processing of data within the $data array.
		 *
		 * @since TBD
		 *
		 * @param Cart_Interface $cart The cart object.
		 * @param array          $data The data to be processed by the cart.
		 */
		do_action( 'tec_tickets_commerce_cart_process', $this, $data );

		// Saved added items to the cart.
		$this->save();

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Add the full set of parameters to the items in the cart.
	 *
	 * @param array $items The items in the cart.
	 *
	 * @return array The items in the cart with the full set of parameters.
	 */
	protected function add_full_item_params( array $items ): array {
		return array_map(
			function ( $item ) {
				$type = $item['type'] ?? 'ticket';
				switch ( $type ) {
					case 'ticket':
						return $this->add_ticket_params( $item );

					default:
						/**
						 * Filter the full item parameters for the cart.
						 *
						 * This allows for further processing of the item parameters.
						 *
						 * If the item shouldn't be processed, `null` should be returned. Otherwise,
						 * an array of the full item parameters should be returned.
						 *
						 * @since TBD
						 *
						 * @param array|null $params The full item parameters for the cart.
						 * @param array      $item   The item in the cart.
						 * @param string     $type   The type of item.
						 */
						return apply_filters( 'tec_tickets_commerce_cart_add_full_item_params', null, $item, $type );
				}
			},
			$items
		);
	}

	/**
	 * Add the ticket parameters to the item in the cart.
	 *
	 * @param array $item The item in the cart.
	 *
	 * @return ?array The item in the cart with the full set of parameters.
	 */
	protected function add_ticket_params( $item ) {
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
	}

	/**
	 * Get the total value of the cart, including additional values such as fees or discounts.
	 *
	 * This method calculates the total by first computing the subtotal from all items in the cart,
	 * and then applying any additional values (e.g., fees or discounts) provided via the `tec_tickets_commerce_get_cart_additional_values` filter.
	 *
	 * @since 5.10.0
	 * @since 5.18.0 Refactored logic, to include a new filter.
	 *
	 * @return float The total value of the cart, or null if there are no items.
	 */
	public function get_cart_total() {
		// If the total has already been calculated, return it.
		if ( $this->total_calculated ) {
			return $this->cart_total;
		}

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

		// Set the total and mark it as calculated.
		$this->cart_total       = $total_value->get_decimal();
		$this->total_calculated = true;

		return $this->cart_total;
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
		// If the subtotal has already been calculated, return it.
		if ( $this->subtotal_calculated ) {
			return (float) $this->cart_subtotal;
		}

		// Set the subtotal to 0 before calculating it.
		$this->cart_subtotal = 0.0;

		// Calculate the total from the subtotals of each item.
		$items = $this->get_items_in_cart( true );
		foreach ( $items as $item ) {
			$this->cart_subtotal += $item['sub_total']->get_decimal();
		}

		// Set the subtotal as calculated.
		$this->subtotal_calculated = true;

		return $this->cart_subtotal;
	}

	/**
	 * Reset the cart calculations.
	 *
	 * After calling this method, calculations will be performed again.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function reset_calculations() {
		$this->subtotal_calculated = false;
		$this->total_calculated    = false;
	}
}

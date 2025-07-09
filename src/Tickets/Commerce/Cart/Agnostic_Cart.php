<?php
/**
 * A cart that is agnostic to what types of items it contains.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class Agnostic_Cart
 *
 * @since 5.21.0
 */
class Agnostic_Cart extends Abstract_Cart {

	use Cart_Trait;

	/**
	 * @var Cart_Item[] The list of items.
	 */
	protected array $items = [];

	/**
	 * Gets the cart items from the cart.
	 *
	 * This method should include any persistence by the cart implementation.
	 *
	 * @since 5.21.0
	 *
	 * @return array The items in the cart.
	 */
	public function get_items() {
		if ( ! empty( $this->items ) ) {
			return $this->get_items_as_array();
		}

		if ( ! $this->exists() ) {
			return [];
		}

		$this->load_items_from_transient();

		return $this->get_items_as_array();
	}

	/**
	 * Loads the items from the transient.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function load_items_from_transient() {
		$items = get_transient( $this->get_transient_key( $this->get_hash() ) );
		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->set_items_from_array( $items );
		}

		$this->reset_calculations();
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
		// If the cart hash matches what we already have, don't set it again.
		if ( $this->get_hash() === $hash ) {
			return;
		}

		parent::set_hash( $hash );
		$this->load_items_from_transient();
	}

	/**
	 * Gets the cart items as plain items instead of objects.
	 *
	 * @since 5.21.0
	 *
	 * @return array The items in the cart.
	 */
	protected function get_items_as_array(): array {
		return array_map(
			fn( $item ) => $item->to_array(),
			$this->items
		);
	}

	/**
	 * Sets the cart items from a plain items array.
	 *
	 * @since 5.21.0
	 *
	 * @param array $items The items to set.
	 */
	protected function set_items_from_array( array $items ) {
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
	 * @since 5.21.0
	 *
	 * @return bool Whether the cart was saved.
	 */
	public function save() {
		$cart_hash = $this->get_hash();

		// If we don't have a cart hash, generate one.
		if ( empty( $cart_hash ) ) {
			// If we still don't have a cart hash, bail.
			if ( false === $this->generate_and_set_cart_hash() ) {
				return false;
			}

			$cart_hash = $this->get_hash();
		}

		// If we don't have any items, clear the cart and bail.
		if ( ! $this->has_items() ) {
			$this->clear();

			return false;
		}

		set_transient(
			$this->get_transient_key( $cart_hash ),
			$this->get_items_as_array(),
			$this->get_transient_expiration()
		);

		tribe( Cart::class )->set_cart_hash_cookie( $cart_hash );

		return true;
	}

	/**
	 * Generates and sets the cart hash.
	 *
	 * @since 5.21.0
	 *
	 * @return bool Whether the cart hash was generated and set.
	 */
	protected function generate_and_set_cart_hash(): bool {
		$cart_hash = tribe( Cart::class )->get_cart_hash( true );

		if ( false === $cart_hash ) {
			return false;
		}

		$this->set_hash( $cart_hash );

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
		$this->cart_subtotal = new Precision_Value( 0 );
		$this->cart_total    = new Precision_Value( 0 );
		$this->reset_calculations();
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since 5.21.0
	 *
	 * @param array $unused_criteria Unused extra criteria.
	 *
	 * @return bool Whether the cart exists or not.
	 */
	public function exists( array $unused_criteria = [] ) {
		$hash = tribe( Cart::class )->get_cart_hash();
		if ( empty( $hash ) ) {
			return false;
		}

		return (bool) get_transient( $this->get_transient_key( $hash ) );
	}

	/**
	 * Whether the cart contains items or not.
	 *
	 * @since 5.21.0
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
	 * @since 5.21.0
	 *
	 * @param string $item_id The item ID.
	 *
	 * @return bool Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id ) {
		return array_key_exists( $item_id, $this->items );
	}

	/**
	 * Insert or update an item.
	 *
	 * @since 5.21.0
	 *
	 * @param string|int $item_id    The item ID.
	 * @param int        $quantity   The quantity of the item. If the item exists, this quantity will override
	 *                               the previous quantity. Passing 0 will remove the item from the cart entirely.
	 * @param array      $extra_data Extra data to save to the item.
	 *
	 * @return void
	 */
	public function upsert_item( $item_id, int $quantity, array $extra_data = [] ) {
		$quantity = abs( $quantity );

		// If the quantity is zero, just remove the item.
		if ( $quantity === 0 ) {
			$this->remove_item( $item_id );

			return;
		}

		// Update the item if it exists, otherwise add it.
		if ( $this->has_item( $item_id ) ) {
			$this->update_item( $item_id, $quantity, $extra_data );
		} else {
			$this->add_item( $item_id, $quantity, $extra_data );
		}
	}

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param int|string $item_id    The item ID.
	 * @param int        $quantity   The quantity to add.
	 * @param array      $extra_data Extra data to save to the item.
	 *
	 * @throws InvalidArgumentException If the quantity is less than 0.
	 */
	private function add_item( $item_id, int $quantity, array $extra_data = [] ) {
		// If the quantity is zero or less, throw an exception.
		if ( $quantity <= 0 ) {
			throw new InvalidArgumentException( 'Quantity must be greater than 0.' );
		}

		// Allow for the type of item to be passed in.
		$type = $extra_data['type'] ?? 'ticket';
		unset( $extra_data['type'] );

		// Add the item to the array of items.
		$this->items[ $item_id ] = new Cart_Item(
			[
				"{$type}_id" => $item_id,
				'quantity'   => $quantity,
				'type'       => $type,
				'extra'      => $extra_data ?? [],
			]
		);

		$this->reset_calculations();
	}

	/**
	 * Update an item in the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param string $item_id    The item ID.
	 * @param int    $quantity   The quantity to update.
	 * @param ?array $extra_data Extra data to save to the item.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the item does not exist in the cart.
	 */
	private function update_item( $item_id, int $quantity, ?array $extra_data = null ): void {
		// Nothing to do with no quantity.
		if ( $quantity === 0 ) {
			return;
		}

		// Ensure the item exists.
		if ( ! $this->has_item( $item_id ) ) {
			throw new InvalidArgumentException( 'Item not found in cart.' );
		}

		$item_object             = $this->items[ $item_id ];
		$item_object['quantity'] = $quantity;

		// Maybe update the extra data.
		if ( null !== $extra_data ) {
			$item_object['extra'] = $extra_data;
		}

		$this->reset_calculations();
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param int|string $item_id The item ID.
	 */
	public function remove_item( $item_id ) {
		unset( $this->items[ $item_id ] );
		$this->reset_calculations();
	}

	/**
	 * Process the items in the cart.
	 *
	 * @since 5.21.0
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

			// Update quantity in cart.
			$this->upsert_item( $ticket['ticket_id'], $quantity, $ticket['extra'] );
		}

		/**
		 * Fires after the ticket data has been processed.
		 *
		 * This allows for further processing of data within the $data array.
		 *
		 * @since 5.21.0
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
						 * @since 5.21.0
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

		$this->items_have_full_params = true;

		return $this->full_param_items;
	}

	/**
	 * Add the ticket parameters to the item in the cart.
	 *
	 * @since 5.21.0
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
}

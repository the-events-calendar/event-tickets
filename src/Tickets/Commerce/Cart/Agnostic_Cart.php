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
	 * @var array The list of items.
	 */
	protected array $items = [];

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
			return $this->items;
		}

		if ( ! $this->exists() ) {
			return [];
		}

		$items = get_transient( $this->get_transient_key( $this->get_hash() ) );
		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->items = $items;
		}

		return $this->items;
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

		set_transient( $this->get_transient_key( $cart_hash ), $this->items, DAY_IN_SECONDS );
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

		// clear cart items data.
		$this->items      = [];
		$this->cart_total = null;
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since TBD
	 *
	 * @param array $criteria
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
	 *
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
	 * @param string $item_id
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
	 * @param array      $extra_data Extra data to save to the item.
	 */
	public function add_item( $item_id, $quantity, array $extra_data = [] ) {
		// Allow for the type of item to be passed in.
		if ( array_key_exists( 'type', $extra_data ) ) {
			$type = $extra_data['type'];
			unset( $extra_data['type'] );
		} else {
			$type = 'ticket';
		}

		// Set the ID key based on the type of item, defaulting to ticket types.
		$id_key = "{$type}_id";

		// If the item is already in the cart, update the quantity.
		$current_quantity = $this->has_item( $item_id );
		if ( false !== $current_quantity ) {
			$this->update_item( $item_id, $quantity, $extra_data );

			return;
		}

		$this->items[ $item_id ] = [
			$id_key    => $item_id,
			'quantity' => $quantity,
			'extra'    => $extra_data,
		];
	}

	/**
	 * Update an item in the cart.
	 *
	 * @param string $item_id    The item ID.
	 * @param int    $quantity   The quantity to update.
	 * @param array  $extra_data Extra data to save to the item.
	 *
	 * @return void
	 */
	protected function update_item( $item_id, int $quantity, array $extra_data = [] ): void {
		$item_data    = $this->items[ $item_id ];
		$new_quantity = $item_data['quantity'] + $quantity;

		// If the quantity is less than 1, remove the item from the cart.
		if ( $new_quantity < 1 ) {
			$this->remove_item( $item_id );

			return;
		}

		// Maybe update the extra data.
		if ( ! empty( $extra_data ) ) {
			$item_data['extra'] = array_merge( $item_data['extra'], $extra_data );
		}

		$this->items[ $item_id ] = $item_data;
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
			$available = $tickets_handler->get_ticket_max_purchase( $ticket['ticket_id'] );

			// Bail if ticket does not have enough available capacity.
			if ( ( -1 !== $available && $available < $ticket['quantity'] ) || ! $ticket['obj']->date_in_range() ) {
				$error_code = 'ticket-capacity-not-available';

				$errors[] = new \WP_Error(
					$error_code, sprintf( $messages->get_message( $error_code ), $ticket['obj']->name ), [
					'ticket'        => $ticket,
					'max_available' => $available,
				]
				);
				continue;
			}

			// Enforces that the min to add is 1.
			$ticket['quantity'] = max( 1, (int) $ticket['quantity'] );

			// Add to / update quantity in cart.
			$this->add_item( $ticket['ticket_id'], $ticket['quantity'], $ticket['extra'] );
		}

		/**
		 * Fires after the ticket data has been processed.
		 *
		 * This allows for further processing of data within the $data array.
		 *
		 * @since TBD
		 *
		 * @param Unmanaged_Cart $cart The cart object.
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
}

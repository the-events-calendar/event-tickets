<?php
/**
 * RSVP V2 Cart implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */

namespace TEC\Tickets\RSVP\V2\Cart;

use TEC\Tickets\Commerce\Cart\Abstract_Cart;
use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\Commerce\Values\Precision_Value;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class RSVP_Cart.
 *
 * Handles cart operations for RSVP tickets.
 * Uses separate storage from TC cart to prevent mixing RSVP items with paid tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */
class RSVP_Cart extends Abstract_Cart implements Cart_Interface {
	/**
	 * Cart transient key prefix for RSVP carts.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const TRANSIENT_PREFIX = 'tec_rsvp_cart_';

	/**
	 * The list of items in the cart.
	 *
	 * @since TBD
	 *
	 * @var array|null
	 */
	protected $items = null;

	/**
	 * Gets the name of the transient based on the cart hash.
	 *
	 * Overrides the parent to use RSVP-specific prefix.
	 *
	 * @since TBD
	 *
	 * @param string|null $id The cart hash.
	 *
	 * @return string The transient key.
	 */
	protected function get_transient_key( ?string $id ): string {
		return self::TRANSIENT_PREFIX . md5( $id ?? '' );
	}

	/**
	 * Gets the cart mode.
	 *
	 * @since TBD
	 *
	 * @return string The cart mode.
	 */
	public function get_mode(): string {
		return 'rsvp';
	}

	/**
	 * RSVPs do not have a public cart page.
	 *
	 * @since TBD
	 *
	 * @return bool Always false for RSVP carts.
	 */
	public function has_public_page(): bool {
		return false;
	}

	/**
	 * Gets the cart total.
	 *
	 * RSVPs are always free, so this always returns 0.
	 *
	 * @since TBD
	 *
	 * @return float Always returns 0.
	 */
	public function get_cart_total(): float {
		return 0.0;
	}

	/**
	 * Gets the cart subtotal.
	 *
	 * RSVPs are always free, so this always returns 0.
	 *
	 * @since TBD
	 *
	 * @return float Always returns 0.
	 */
	public function get_cart_subtotal(): float {
		return 0.0;
	}

	/**
	 * Checks if the cart requires payment.
	 *
	 * RSVPs never require payment.
	 *
	 * @since TBD
	 *
	 * @return bool Always returns false.
	 */
	public function requires_payment(): bool {
		return false;
	}

	/**
	 * Saves the cart to transient storage.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the save was successful.
	 */
	public function save(): bool {
		$cart_hash = $this->get_hash();

		if ( empty( $cart_hash ) ) {
			$cart_hash = $this->generate_hash();
			$this->set_hash( $cart_hash );
		}

		if ( ! $this->has_items() ) {
			$this->clear();
			return false;
		}

		return set_transient(
			$this->get_transient_key( $cart_hash ),
			$this->items,
			$this->get_transient_expiration()
		);
	}

	/**
	 * Gets the cart items.
	 *
	 * @since TBD
	 *
	 * @return array The items in the cart.
	 */
	public function get_items(): array {
		if ( null !== $this->items ) {
			return $this->items;
		}

		$cart_hash = $this->get_hash();

		if ( empty( $cart_hash ) ) {
			return [];
		}

		$items = get_transient( $this->get_transient_key( $cart_hash ) );

		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->items = $items;
			return $this->items;
		}

		return [];
	}

	/**
	 * Clears the cart.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function clear(): void {
		$cart_hash = $this->get_hash();

		if ( ! empty( $cart_hash ) ) {
			delete_transient( $this->get_transient_key( $cart_hash ) );
		}

		$this->set_hash( '' );
		$this->items         = [];
		$this->cart_subtotal = new Precision_Value( 0.0 );
		$this->cart_total    = new Precision_Value( 0.0 );
		$this->reset_calculations();
	}

	/**
	 * Checks if a cart exists.
	 *
	 * @since TBD
	 *
	 * @param array $criteria Additional criteria (not used for RSVP cart).
	 *
	 * @return bool Whether the cart exists.
	 */
	public function exists( array $criteria = [] ): bool {
		$cart_hash = $this->get_hash();

		if ( empty( $cart_hash ) ) {
			return false;
		}

		return (bool) get_transient( $this->get_transient_key( $cart_hash ) );
	}

	/**
	 * Checks if the cart has items.
	 *
	 * @since TBD
	 *
	 * @return bool|int The number of items or false if empty.
	 */
	public function has_items() {
		$items = $this->get_items();

		if ( empty( $items ) ) {
			return false;
		}

		return count( $items );
	}

	/**
	 * Checks if a specific item is in the cart.
	 *
	 * @since TBD
	 *
	 * @param string|int $item_id The item ID.
	 *
	 * @return bool|int The quantity in the cart or false if not found.
	 */
	public function has_item( $item_id ) {
		$items = $this->get_items();

		return ! empty( $items[ $item_id ]['quantity'] ) ? (int) $items[ $item_id ]['quantity'] : false;
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @since TBD
	 *
	 * @param int|string $item_id The item ID.
	 *
	 * @return void
	 */
	public function remove_item( $item_id ): void {
		if ( $this->has_item( $item_id ) ) {
			unset( $this->items[ $item_id ] );
			$this->reset_calculations();
			$this->save();
		}
	}

	/**
	 * Inserts or updates an item in the cart.
	 *
	 * @since TBD
	 *
	 * @param string|int $item_id    The item ID.
	 * @param int        $quantity   The quantity.
	 * @param array      $extra_data Extra data to save with the item.
	 *
	 * @return void
	 */
	public function upsert_item( $item_id, int $quantity, array $extra_data = [] ): void {
		if ( $quantity <= 0 ) {
			$this->remove_item( $item_id );
			return;
		}

		if ( null === $this->items ) {
			$this->items = [];
		}

		$this->items[ $item_id ] = [
			'ticket_id'  => $item_id,
			'quantity'   => $quantity,
			'extra_data' => $extra_data,
		];

		$this->reset_calculations();
		$this->save();
	}

	/**
	 * Processes cart data.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to process. Expected format: ['tickets' => [['ticket_id' => int, 'quantity' => int, 'extra' => array], ...]].
	 *
	 * @return array|bool True on success, array of WP_Error on validation failures.
	 */
	public function process( array $data = [] ) {
		if ( empty( $data ) || empty( $data['tickets'] ) ) {
			return false;
		}

		// Clear existing cart.
		$this->clear();

		// Generate a new hash for this cart.
		$cart_hash = $this->generate_hash();
		$this->set_hash( $cart_hash );

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$errors = [];

		foreach ( $data['tickets'] as $ticket ) {
			if ( empty( $ticket['ticket_id'] ) || empty( $ticket['quantity'] ) ) {
				continue;
			}

			$ticket_id = absint( $ticket['ticket_id'] );
			$quantity  = max( 1, absint( $ticket['quantity'] ) );
			$extra     = $ticket['extra'] ?? [];

			// Validate ticket exists.
			$ticket_post = get_post( $ticket_id );

			if ( ! $ticket_post ) {
				$errors[] = new \WP_Error(
					'tec_tickets_rsvp_v2_invalid_ticket',
					sprintf(
						/* translators: %d: ticket ID */
						__( 'Ticket %d does not exist.', 'event-tickets' ),
						$ticket_id
					)
				);
				continue;
			}

			// Check capacity using the ticket object if available.
			$ticket_obj = $ticket['obj'] ?? \Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

			if ( $ticket_obj ) {
				$can_add = $tickets_handler->ticket_has_capacity( $ticket_id, $quantity, $ticket_obj );

				if ( is_wp_error( $can_add ) ) {
					$errors[] = $can_add;
					continue;
				}
			}

			$this->upsert_item( $ticket_id, $quantity, $extra );
		}

		/**
		 * Fires after RSVP cart data has been processed.
		 *
		 * @since TBD
		 *
		 * @param RSVP_Cart $cart The cart object.
		 * @param array     $data The processed data.
		 */
		do_action( 'tec_tickets_rsvp_v2_cart_process', $this, $data );

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Prepares data for cart processing.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to prepare.
	 *
	 * @return array The prepared data.
	 */
	public function prepare_data( array $data = [] ): array {
		/**
		 * Filters the data before it is processed by the RSVP cart.
		 *
		 * @since TBD
		 *
		 * @param array     $data The data to be processed.
		 * @param RSVP_Cart $cart The cart object.
		 */
		return (array) apply_filters( 'tec_tickets_rsvp_v2_cart_prepare_data', $data, $this );
	}

	/**
	 * Generates a unique cart hash.
	 *
	 * @since TBD
	 *
	 * @return string The generated hash.
	 */
	protected function generate_hash(): string {
		return md5( uniqid( 'rsvp_', true ) . wp_generate_password( 16, true, true ) );
	}
}

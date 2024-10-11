<?php
/**
 * Cart class for managing items in the cart.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

use TEC\Tickets\Commerce\Utils\Value;

/**
 * Class Cart
 *
 * Represents a shopping cart for handling items such as tickets, fees, and coupons.
 */
class Cart {
	/**
	 * @var array The collection of all items in the cart, indexed by item ID.
	 */
	protected array $items = [];

	/**
	 * @var array The collection of items, categorized by type.
	 */
	protected array $items_by_type = [
		'fee'    => [],
		'coupon' => [],
		'ticket' => [],
	];

	/**
	 * Adds an item to the cart. If the item already exists, it updates the quantity.
	 * If the item's quantity is zero, it removes the item from the cart.
	 *
	 * @param mixed $item The item to add to the cart.
	 *
	 * @return void
	 */
	public function add_item( $item ): void {
		$type    = $item->get_type() ?? 'ticket';
		$item_id = $item->get_id( true );

		// If the item has a quantity of zero, remove it from the cart.
		if ( $item->get_quantity() <= 0 ) {
			$this->remove_item( $item_id );
			return;
		}

		if ( $this->has_item( $item_id ) ) {
			// Update the quantity if the item already exists.
			$this->items[ $item_id ]->set_quantity(
				$this->items[ $item_id ]->get_quantity() + $item->get_quantity()
			);

			// If the updated quantity is zero, remove the item from the cart.
			if ( $this->items[ $item_id ]->get_quantity() <= 0 ) {
				$this->remove_item( $item_id );
				return;
			}
		} else {
			// Add the item if it doesn't already exist.
			$this->items_by_type[ $type ][ $item_id ] = $item;
			$this->items[ $item_id ]                  = $item;
			$item->added_to_cart( $this ); // Trigger additional actions when the item is added to the cart.
		}
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @param string|int $item_id The ID of the item to remove.
	 *
	 * @return void
	 */
	public function remove_item( $item_id ): void {
		if ( isset( $this->items[ $item_id ] ) ) {
			unset( $this->items[ $item_id ] );
			foreach ( $this->items_by_type as $type => $items ) {
				unset( $this->items_by_type[ $type ][ $item_id ] );
			}
		}
	}

	/**
	 * Retrieves all items in the cart.
	 *
	 * @return array The collection of items in the cart.
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Retrieves a specific item by its ID.
	 *
	 * @param string|int $item_id The ID of the item to retrieve.
	 *
	 * @return mixed|null The item if found, null otherwise.
	 */
	public function get_item( $item_id ) {
		return $this->items[ $item_id ] ?? null;
	}

	/**
	 * Retrieves items by a specific type (e.g., 'ticket', 'fee', 'coupon').
	 *
	 * @param string $type The type of items to retrieve.
	 *
	 * @return array The collection of items for the given type.
	 */
	public function get_items_by_type( string $type ): array {
		return $this->items_by_type[ $type ] ?? [];
	}

	/**
	 * Checks if a specific item is in the cart.
	 *
	 * @param string|int $item_id The ID of the item to check.
	 *
	 * @return bool True if the item is in the cart, false otherwise.
	 */
	public function has_item( $item_id ): bool {
		return array_key_exists( $item_id, $this->items );
	}

	/**
	 * Clears all items from the cart.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->items         = [];
		$this->items_by_type = [
			'fee'    => [],
			'coupon' => [],
			'ticket' => [],
		];
	}

	/**
	 * Calculates and returns the total value of all items in the cart.
	 *
	 * Ensures the total value is never below zero and handles cases where the total can be considered free.
	 *
	 * @return Value The total value as a Value object.
	 */
	public function get_total(): Value {
		$total = 0;

		// Calculate the total value of items in the cart.
		foreach ( $this->items as $item ) {
			$total += $item->get_amount( $this->get_subtotal() );

			// If the total is no longer an integer, it means an overflow occurred.
			if ( ! is_int( $total ) ) {
				// Cap the total at the max for a 32-bit integer.
				$total = ( 2 ** 31 ) - 1;

				return Value::create( $total / 100 );
			}
		}

		// If the total is less than or equal to zero, check if it should be considered zero.
		if ( $total <= 0 ) {
			$total_value = Value::create( $total / 100 );

			// If the total can be considered free, return zero.
			if ( $this->is_free_based_on_items( $total_value ) || $total < 0 ) {
				return Value::create( 0 );
			}

			// If the subtotal is positive, use the subtotal as the total.
			$subtotal = $this->get_subtotal();
			if ( $subtotal > 0 ) {
				return Value::create( $subtotal / 100 );
			}

			// If none of the conditions apply, return zero.
			return Value::create( 0 );
		}

		// Ensure the total is never negative and return the final value.
		$total = max( 0, $total );

		return Value::create( $total / 100 );
	}

	/**
	 * Determines if the total can be considered free.
	 *
	 * The total can be marked as free if:
	 * - It is negative, and there are no positive items counted in the subtotal.
	 * - All counted items in the cart have zero or negative values.
	 *
	 * @param Value $total The current total value.
	 *
	 * @return bool True if the total can be considered free, false otherwise.
	 */
	protected function is_free_based_on_items( Value $total ): bool {
		$total_value       = $total->get_integer();
		$has_positive_item = false;

		// Check each item in the cart.
		foreach ( $this->items as $item ) {
			if ( $item->is_counted_in_subtotal() && $item->get_amount() > 0 ) {
				// If there is at least one positive item, the total cannot be free.
				$has_positive_item = true;
				break;
			}
		}

		// If the total is negative and there are no positive items, mark it as free.
		if ( $total_value < 0 && ! $has_positive_item ) {
			return true;
		}

		// If no positive items were found, and the total is zero or less, it can be free.
		if ( ! $has_positive_item && $total_value <= 0 ) {
			return true;
		}

		// Otherwise, the total cannot be considered free.
		return false;
	}

	/**
	 * Calculates the subtotal value of all items that should be counted in the subtotal.
	 *
	 * Uses standard integer addition and caps the subtotal at PHP_INT_MAX if it exceeds that value.
	 *
	 * @return int The subtotal value in cents.
	 */
	public function get_subtotal(): int {
		$subtotal = 0;

		foreach ( $this->items as $item ) {
			if ( $item->is_counted_in_subtotal() ) {
				$subtotal += $item->get_amount();

				// If the subtotal exceeds PHP_INT_MAX, cap it at PHP_INT_MAX.
				if ( $subtotal > PHP_INT_MAX ) {
					$subtotal = PHP_INT_MAX;
					break; // No need to continue adding, as we have reached the cap.
				}
			}
		}

		return (int) $subtotal;
	}

	/**
	 * Calculates and returns the total value for a specific type of item (e.g., 'ticket', 'fee', 'coupon').
	 *
	 * @param string $type The type of items to calculate the total for.
	 *
	 * @return Value The total value as a Value object.
	 */
	public function get_total_by_type( string $type ): Value {
		$total = 0;
		foreach ( $this->get_items_by_type( $type ) as $item ) {
			$total += $item->get_amount( $this->get_subtotal() );
		}

		return Value::create( $total / 100 );
	}
}

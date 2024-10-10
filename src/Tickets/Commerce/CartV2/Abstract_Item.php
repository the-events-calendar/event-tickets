<?php
/**
 * Abstract class for Cart Item.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

use InvalidArgumentException;

/**
 * Class Abstract_Item
 *
 * Represents a base class for items that can be added to the cart, implementing common functionality.
 */
abstract class Abstract_Item implements Item_Interface {
	/**
	 * @var string|int The ID of the item.
	 */
	protected $id;

	/**
	 * @var int The quantity of the item.
	 */
	protected $quantity;

	/**
	 * @var int The value of the item in cents. Can be negative for discounts.
	 */
	protected $value;

	/**
	 * @var string|null The subtype of the item (e.g., 'flat', 'percent').
	 */
	protected $sub_type;

	/**
	 * @var string The type of the item (e.g., 'ticket', 'fee').
	 */
	protected $type;

	/**
	 * Abstract_Item constructor.
	 *
	 * @param string|int  $id       The ID of the item.
	 * @param int         $quantity The quantity of the item.
	 * @param int         $value    The value of the item in cents.
	 * @param string|null $sub_type Optional. The subtype of the item (e.g., 'flat', 'percent').
	 */
	public function __construct( $id, int $quantity, int $value, ?string $sub_type = 'flat' ) {
		$this->id       = $id;
		$this->quantity = $quantity;
		$this->value    = $value;
		$this->sub_type = $sub_type;
	}

	/**
	 * Retrieves the ID of the item.
	 *
	 * @param bool $prefixed Optional. Whether to return a prefixed ID. Default false.
	 *
	 * @return string|int The item's ID, possibly prefixed.
	 */
	public function get_id( bool $prefixed = false ) {
		return $prefixed ? "{$this->type}_{$this->id}" : $this->id;
	}

	/**
	 * Retrieves the quantity of the item.
	 *
	 * @return int The quantity of the item.
	 */
	public function get_quantity(): int {
		return $this->quantity;
	}

	/**
	 * Sets the quantity of the item.
	 *
	 * @param int $quantity The new quantity to set.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If the quantity is less than zero.
	 */
	public function set_quantity( int $quantity ): void {
		if ( $quantity < 0 ) {
			throw new InvalidArgumentException( 'Quantity must be a positive integer.' );
		}
		$this->quantity = $quantity;
	}

	/**
	 * Retrieves the value of the item in cents.
	 *
	 * @return int The value of the item in cents.
	 */
	public function get_value(): int {
		return $this->value;
	}

	/**
	 * Retrieves the type of the item (e.g., 'ticket', 'fee').
	 *
	 * @return string The type of the item.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Retrieves the subtype of the item (e.g., 'flat', 'percent').
	 *
	 * @return string The subtype of the item, or null if not set.
	 */
	public function get_sub_type(): string {
		return $this->sub_type;
	}

	/**
	 * Calculates the amount for the item, potentially based on a given subtotal.
	 *
	 * @param int|null $subtotal Optional. The subtotal value to base the calculation on.
	 *
	 * @return int The calculated amount in cents.
	 */
	abstract public function get_amount( ?int $subtotal = null ): int;

	/**
	 * Checks if the item should be counted in the subtotal.
	 *
	 * @return bool True if the item is included in the subtotal calculation, false otherwise.
	 */
	abstract public function is_counted_in_subtotal(): bool;

	/**
	 * Performs an action when the item is added to the cart.
	 *
	 * @param Cart $cart The cart to which the item is being added.
	 *
	 * @return void
	 */
	public function added_to_cart( Cart $cart ): void {
		// Default implementation: do nothing. Subclasses may override this.
	}

	/**
	 * Checks if the item is in stock.
	 *
	 * @return bool True if the item is in stock, false otherwise.
	 */
	abstract public function is_in_stock(): bool;
}

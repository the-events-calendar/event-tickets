<?php
/**
 * Cart Item class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

/**
 * Class Item
 *
 * @since TBD
 */
class Item {

	/**
	 * @var string The item ID.
	 */
	protected string $id;

	/**
	 * @var string The item type.
	 */
	protected string $type;

	/**
	 * @var int The item quantity.
	 */
	protected int $quantity;

	/**
	 * Item constructor.
	 *
	 * @param string $id       The item ID.
	 * @param string $type     The item type.
	 * @param int    $quantity The item quantity.
	 */
	public function __construct( string $id, string $type, int $quantity ) {
		$this->id       = $id;
		$this->type     = $type;
		$this->quantity = $quantity;
	}

	/**
	 * Get the item type.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get the item quantity.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_quantity(): int {
		return $this->quantity;
	}

	/**
	 * Get the item ID.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}
}

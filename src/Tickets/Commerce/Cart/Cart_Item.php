<?php
/**
 * Cart Item class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

use ArrayAccess;
use ReturnTypeWillChange;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Integer_Value;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Value_Interface;
use Tribe\Traits\Array_Access;

/**
 * Class Cart_Item
 *
 * @since TBD
 */
class Cart_Item implements ArrayAccess {

	use Array_Access;

	/**
	 * Cart_Item constructor.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to set.
	 */
	public function __construct( array $data = [] ) {
		// Set default values for the item.
		$data = array_merge(
			[
				'type'     => 'ticket',
				'quantity' => 1,
			],
			$data
		);

		foreach ( $data as $index => $value ) {
			$this->offsetSet( $index, $value );
		}
	}

	/**
	 * Get an offset.
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetget.php
	 * @since TBD
	 *
	 * @param mixed $offset The offset to get.
	 *
	 * @return mixed The offset value, or null if it does not exist.
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		$value = $this->data[ $offset ] ?? null;

		return $value instanceof Value_Interface ? $value->get() : $value;
	}

	/**
	 * Set an offset.
	 *
	 * @link  http://php.net/manual/en/arrayaccess.offsetset.php
	 * @since TBD
	 *
	 * @param mixed $offset The offset to set.
	 * @param mixed $value  The value to set.
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ): void {
		switch ( $offset ) {
			case 'quantity':
				$this->data[ $offset ] = Integer_Value::from_number( $value );
				break;

			default:
				$this->data[ $offset ] = $value;
				break;
		}
	}

	/**
	 * Add a quantity to the item.
	 *
	 * @since TBD
	 *
	 * @param int $new_quantity The quantity to add.
	 *
	 * @return int The new quantity.
	 */
	public function add_quantity( int $new_quantity ): int {
		/** @var Integer_Value $quantity */
		$quantity               = $this->data['quantity'];
		$this->data['quantity'] = $quantity->add( $new_quantity );

		return $this->data['quantity']->get();
	}

	/**
	 * Get the item data as an array.
	 *
	 * @since TBD
	 *
	 * @return array The item data.
	 */
	public function to_array(): array {
		return array_map(
			static function ( $value ) {
				return $value instanceof Value_Interface ? $value->get() : $value;
			},
			$this->data
		);
	}
}

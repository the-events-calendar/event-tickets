<?php
/**
 * Valid Types trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Traits;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Order_Modifiers\Models\Fee;

/**
 * Trait Valid_Types
 *
 * @since TBD
 */
trait Valid_Types {

	/**
	 * Determine if a type is valid.
	 *
	 * @since TBD
	 *
	 * @param string $type The type.
	 *
	 * @return bool
	 */
	protected function is_valid_type( string $type ): bool {
		return array_key_exists( $type, $this->get_valid_types() );
	}

	/**
	 * Get the valid order modifier types.
	 *
	 * @since TBD
	 *
	 * @return array The valid order modifier types.
	 */
	protected function get_valid_types(): array {
		$types = [
			'coupon' => Coupon::class,
			'fee'    => Fee::class,
		];

		/**
		 * Filters the valid order modifier types.
		 *
		 * Note that the keys are the type slugs and the values are the model class. The
		 * key is used to determine whether a type is valid or not. The value
		 * is used to instantiate the model.
		 *
		 * @since TBD
		 *
		 * @param array $types The valid order modifier types.
		 */
		return (array) apply_filters( 'tec_tickets_order_modifier_types', $types );
	}

	/**
	 * Validate the modifier type.
	 *
	 * @param string $type
	 *
	 * @return void
	 * @throws InvalidArgumentException If the type is invalid.
	 */
	protected function validate_type( string $type ) {
		if ( ! $this->is_valid_type( $type ) ) {
			throw new InvalidArgumentException( 'Invalid modifier type.' );
		}
	}
}

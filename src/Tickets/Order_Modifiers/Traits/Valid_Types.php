<?php
/**
 * Valid Types trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Traits;

use InvalidArgumentException;

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
			'coupon' => __( 'Coupon', 'event-tickets' ),
			'fee'    => __( 'Fee', 'event-tickets' ),
		];

		/**
		 * Filters the valid order modifier types.
		 *
		 * Note that the keys are the type slugs and the values are the type labels. The
		 * key is used to determine whether a type is valid or not.
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

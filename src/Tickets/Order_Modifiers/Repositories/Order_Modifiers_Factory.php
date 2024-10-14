<?php
/**
 * Order modifiers factory.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Repositories;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Order_Modifiers_Factory
 *
 * @since TBD
 */
class Order_Modifiers_Factory {

	use Valid_Types;

	/**
	 * Get the repository for a given type.
	 *
	 * @since TBD
	 *
	 * @param string $type The type.
	 *
	 * @return Order_Modifiers
	 */
	public static function get_repository_for_type( string $type ): Order_Modifiers {
		self::validate_type( $type );

		switch ( $type ) {
			case 'fee':
				return new Fees();

			case 'coupon':
				return new Coupons();

			default:
				$class = Order_Modifiers::class;

				/**
				 * Filters the order modifiers repository class for a given type.
				 *
				 * The class must be a child of the Order_Modifiers class.
				 *
				 * @since TBD
				 *
				 * @param string $class The order modifiers repository class.
				 */
				$class = apply_filters( 'tec_tickets_order_modifiers_repository_class', $class, $type );

				if ( ! class_exists( $class ) ) {
					throw new InvalidArgumentException( 'The order modifiers repository class does not exist.' );
				}

				if ( ! $class instanceof Order_Modifiers ) {
					throw new InvalidArgumentException( 'The order modifiers repository class must be a child of Order_Modifiers.' );
				}

				return new $class( $type );
		}
	}
}

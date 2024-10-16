<?php
/**
 * Order modifiers factory.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Repositories\Coupons as CouponsRepository;
use TEC\Tickets\Order_Modifiers\Repositories\Fees as FeesRepository;
use TEC\Tickets\Order_Modifiers\Repositories\Order_Modifiers as ModifiersRepository;
use TEC\Tickets\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Order_Modifiers_Factory
 *
 * @since TBD
 */
class Factory {

	use Valid_Types;

	/**
	 * Get the repository for a given type.
	 *
	 * @since TBD
	 *
	 * @param string $type The type.
	 *
	 * @return ModifiersRepository
	 */
	public static function get_repository_for_type( string $type ): ModifiersRepository {
		self::validate_type( $type );

		switch ( $type ) {
			case 'fee':
				return new FeesRepository();

			case 'coupon':
				return new CouponsRepository();

			default:
				$class = ModifiersRepository::class;

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

				if ( ! $class instanceof ModifiersRepository ) {
					throw new InvalidArgumentException(
						sprintf(
							'The order modifiers repository class must be a child of %s.',
							ModifiersRepository::class
						)
					);
				}

				return new $class( $type );
		}
	}
}

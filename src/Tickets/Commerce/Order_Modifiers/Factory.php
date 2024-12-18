<?php
/**
 * Order modifiers factory.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon as CouponModel;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Fee as FeeModel;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier as ModifierModel;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as CouponsRepository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Fees as FeesRepository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers as ModifiersRepository;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Order_Modifiers_Factory
 *
 * @since 5.18.0
 */
class Factory {

	use Valid_Types;

	/**
	 * Get the repository for a given type.
	 *
	 * @since 5.18.0
	 *
	 * @param string $type The type.
	 *
	 * @return ModifiersRepository The repository instance.
	 * @throws InvalidArgumentException If the type is invalid, or if the repository class is not a child of the ModifiersRepository class.
	 */
	public static function get_repository_for_type( string $type ): ModifiersRepository {
		self::validate_type( $type );

		switch ( $type ) {
			case 'fee':
				return tribe( FeesRepository::class );

			case 'coupon':
				return tribe( CouponsRepository::class );

			default:
				$class = ModifiersRepository::class;

				/**
				 * Filters the order modifiers repository class for a given type.
				 *
				 * The class must be a child of the Repositories\Order_Modifiers class.
				 *
				 * @since 5.18.0
				 *
				 * @param string $class The order modifiers repository class.
				 */
				$class = apply_filters( 'tec_tickets_commerce_order_modifiers_repository_class', $class, $type );

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

	/**
	 * Get the model for a given type.
	 *
	 * @since 5.18.0
	 *
	 * @param string $type       The type.
	 * @param array  $attributes The attributes.
	 *
	 * @return ModifierModel The model instance.
	 * @throws InvalidArgumentException If the type is invalid, or if the model class is not a child of the ModifierModel class.
	 */
	public static function get_model_for_type( string $type, array $attributes ): ModifierModel {
		self::validate_type( $type );

		switch ( $type ) {
			case 'fee':
				return new FeeModel( $attributes );

			case 'coupon':
				return new CouponModel( $attributes );

			default:
				$class = ModifierModel::class;

				/**
				 * Filters the order modifiers model class for a given type.
				 *
				 * The class must be a child of the Models\Order_Modifiers class.
				 *
				 * @since 5.18.0
				 *
				 * @param string $class The order modifiers model class.
				 */
				$class = apply_filters( 'tec_tickets_commerce_order_modifiers_model_class', $class, $type );

				if ( ! class_exists( $class ) ) {
					throw new InvalidArgumentException( 'The order modifiers model class does not exist.' );
				}

				if ( ! $class instanceof ModifierModel ) {
					throw new InvalidArgumentException(
						sprintf(
							'The order modifiers model class must be a child of %s.',
							ModifierModel::class
						)
					);
				}

				return new $class( $attributes );
		}
	}
}

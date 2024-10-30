<?php
/**
 * Valid Types trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Traits;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Models\Coupon as Coupon_Model;
use TEC\Tickets\Order_Modifiers\Models\Fee as Fee_Model;
use TEC\Tickets\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Order_Modifiers\Modifiers\Fee;

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
	protected static function is_valid_type( string $type ): bool {
		return array_key_exists( $type, self::get_valid_types() );
	}

	/**
	 * Get the valid order modifier types.
	 *
	 * @since TBD
	 *
	 * @return array The valid order modifier types.
	 */
	protected static function get_valid_types(): array {
		$types = [
			'coupon' => Coupon_Model::class,
			'fee'    => Fee_Model::class,
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
	protected static function validate_type( string $type ) {
		if ( ! self::is_valid_type( $type ) ) {
			throw new InvalidArgumentException( 'Invalid modifier type.' );
		}
	}

	/**
	 * Get the available order modifier types.
	 *
	 * @since TBD
	 *
	 * @return array The available order modifier types.
	 */
	protected static function get_modifiers(): array {
		static $modifiers = null;

		// Return cached modifiers if available.
		if ( null !== $modifiers ) {
			return $modifiers;
		}

		// Default modifiers with display name, slug, and class.
		$modifiers = [
			'coupon' => [
				'display_name' => __( 'Coupons', 'event-tickets' ),
				'slug'         => 'coupon',
				'class'        => Coupon::class,
			],
			'fee'    => [
				'display_name' => __( 'Fees', 'event-tickets' ),
				'slug'         => 'fee',
				'class'        => Fee::class,
			],
		];

		/**
		 * Filters the list of available modifiers for Order Modifiers.
		 *
		 * This allows developers to add or modify the default list of order modifiers.
		 *
		 * @since TBD
		 *
		 * @param array $modifiers An array of default modifiers, each containing 'display_name', 'slug', and 'class'.
		 */
		$modifiers = (array) apply_filters( 'tec_tickets_order_modifiers', $modifiers );

		// Validate modifiers after the filter.
		$required_keys = [
			'class'        => 1,
			'slug'         => 1,
			'display_name' => 1,
		];

		foreach ( $modifiers as $key => $modifier ) {
			// If array keys are missing, then remove the modifier.
			$missing = array_diff_key( $required_keys, $modifier );
			if ( ! empty( $missing ) ) {
				unset( $modifiers[ $key ] );
			}

			// If the class doesn't exist, then remove the modifier.
			if ( ! class_exists( $modifier['class'] ) ) {
				unset( $modifiers[ $key ] );
			}
		}

		return $modifiers;
	}

	/**
	 * Get the default order modifier type.
	 *
	 * @since TBD
	 *
	 * @return string The default order modifier type.
	 */
	protected static function get_default_type(): string {
		$default_modifier = array_key_first( self::get_valid_types() );

		/**
		 * Filters the default order modifier.
		 *
		 * This filter allows you to set a different default modifier for the order modifiers table. The
		 * default is to use the first key from the array of available modifiers.
		 *
		 * @since TBD
		 *
		 * @param string $default_modifier The default modifier to use.
		 */
		return (string) apply_filters( 'tec_tickets_order_modifier_default_type', $default_modifier );
	}
}

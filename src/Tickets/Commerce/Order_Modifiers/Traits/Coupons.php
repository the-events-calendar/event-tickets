<?php
/**
 * Coupons trait.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Factory;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as Coupons_Repository;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta as Meta_Repository;

/**
 * Trait Coupons
 *
 * @since 5.18.0
 */
trait Coupons {

	/**
	 * Determine if a coupon is valid, using the slug to look up the coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param string $slug The coupon slug.
	 *
	 * @return bool Whether the coupon is valid.
	 */
	protected function is_coupon_slug_valid( string $slug ): bool {
		try {
			$repo = Factory::get_repository_for_type( 'coupon' );

			return $this->is_coupon_valid( $repo->find_by_slug( $slug ) );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Determine if a coupon slug exists.
	 *
	 * @since 5.21.0
	 *
	 * @param string $slug The coupon slug.
	 *
	 * @return bool Whether the coupon slug exists.
	 */
	protected function does_coupon_slug_exist( string $slug ): bool {
		try {
			$repo = tribe( Coupons_Repository::class );

			return $repo->find_by_slug( $slug ) instanceof Coupon;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Determine if a coupon is valid.
	 *
	 * @since 5.21.0
	 *
	 * @param Coupon|Order_Modifier $maybe_coupon The coupon to check.
	 *
	 * @return bool Whether the coupon is valid.
	 */
	protected function is_coupon_valid( $maybe_coupon ): bool {
		try {
			// If it's not a coupon, it's invalid.
			if ( ! $this->is_coupon_modifier( $maybe_coupon ) ) {
				throw new Exception( 'Not a coupon' );
			}

			// If the status isn't active, the coupon is invalid.
			if ( $maybe_coupon->status !== 'active' ) {
				throw new Exception( 'Coupon is not active' );
			}

			// If the coupon is not within its date rante, it is invalid.
			$this->is_coupon_within_date_range( $maybe_coupon );

			// Whether the coupon is still within its usage limit.
			if ( ! $this->coupon_has_uses_remaining( $maybe_coupon->id ) ) {
				throw new Exception( 'Coupon has no uses remaining' );
			}

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Determine if a coupon is within its start and end date range.
	 *
	 * @since 5.21.0
	 *
	 * @param Coupon $coupon The coupon to check.
	 *
	 * @return bool Whether the coupon is within its start and end date range.
	 *
	 * @throws Exception If the coupon end date has passed or the coupon start date is in the future.
	 */
	protected function is_coupon_within_date_range( Coupon $coupon ): bool {
		// Current time stamp for checking start and end dates.
		$current_time = time();

		// If the coupon end date has passed, it is invalid.
		if ( null !== $coupon->end_time && strtotime( $coupon->end_time ) < $current_time ) {
			throw new Exception( 'Coupon end date has passed' );
		}

		// If the coupon start date is in the future, it is invalid.
		if ( null !== $coupon->start_time && strtotime( $coupon->start_time ) > $current_time ) {
			throw new Exception( 'Coupon start date is in the future' );
		}

		return true;
	}

	/**
	 * Determine if a modifier is a coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param Order_Modifier $modifier The modifier to check.
	 *
	 * @return bool
	 */
	protected function is_coupon_modifier( Order_Modifier $modifier ): bool {
		if ( $modifier instanceof Coupon ) {
			return true;
		}

		return $modifier->modifier_type === 'coupon';
	}

	/**
	 * Get the usage limit for a coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return int The usage limit for the coupon. -1 indicates there is no limit.
	 */
	protected function get_coupon_usage_limit( int $coupon_id ): int {
		/** @var Meta_Repository $meta */
		$meta = tribe( Meta_Repository::class );

		$available = $meta->find_by_order_modifier_id_and_meta_key( $coupon_id, 'coupons_available' );

		// If we got null, the coupon is unlimited.
		if ( null === $available ) {
			return -1;
		}

		// If we got null, the coupon is unlimited.
		if ( empty( $available->meta_value ) ) {
			return -1;
		}

		return (int) $available->meta_value;
	}

	/**
	 * Get the number of times a coupon has been used.
	 *
	 * @since 5.21.0
	 *
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return int The number of times the coupon has been used.
	 */
	protected function get_coupon_uses( int $coupon_id ): int {
		try {
			$uses = $this->get_uses_meta( $coupon_id );

			return empty( $uses->meta_value )
				? 0
				: (int) $uses->meta_value;
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Get the meta for a coupon's uses.
	 *
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return Order_Modifier_Meta The meta for the coupon's uses.
	 * @throws Exception If no uses meta is found.
	 */
	protected function get_uses_meta( int $coupon_id ): Order_Modifier_Meta {
		/** @var Meta_Repository $meta */
		$meta = tribe( Meta_Repository::class );

		$uses = $meta->find_by_order_modifier_id_and_meta_key( $coupon_id, 'coupons_uses' );
		if ( ! $uses instanceof Order_Modifier_Meta ) {
			throw new Exception( 'No uses meta found' );
		}

		return $uses;
	}

	/**
	 * Determine if a coupon has uses remaining.
	 *
	 * @since 5.21.0
	 *
	 * @param int $coupon_id The coupon ID.
	 *
	 * @return bool Whether the coupon has uses remaining.
	 */
	protected function coupon_has_uses_remaining( int $coupon_id ): bool {
		$limit = $this->get_coupon_usage_limit( $coupon_id );

		// If the limit is -1, the coupon is unlimited.
		if ( -1 === $limit ) {
			return true;
		}

		return $this->get_coupon_uses( $coupon_id ) < $limit;
	}

	/**
	 * Add a coupon use.
	 *
	 * @since 5.21.0
	 *
	 * @param int $coupon_id The coupon ID.
	 * @param int $quantity  The number of uses to add.
	 *
	 * @return void
	 */
	protected function add_coupon_use( int $coupon_id, int $quantity = 1 ) {
		try {
			$existing_uses = $this->get_uses_meta( $coupon_id );
		} catch ( Exception $e ) {
			$existing_uses = new Order_Modifier_Meta(
				[
					'order_modifier_id' => $coupon_id,
					'meta_key'          => 'coupons_uses',
					'meta_value'        => 0,
				]
			);
		}

		$existing_uses->meta_value = (int) $existing_uses->meta_value + $quantity;
		$existing_uses->save();
	}

	/**
	 * Remove a coupon use.
	 *
	 * @since 5.21.0
	 *
	 * @param int $coupon_id The coupon ID.
	 * @param int $quantity  The number of uses to remove.
	 *
	 * @return void
	 */
	protected function remove_coupon_use( int $coupon_id, int $quantity = 1 ) {
		try {
			$existing_uses = $this->get_uses_meta( $coupon_id );

			// Use max() to ensure we don't set the usage to a negative number.
			$existing_uses->meta_value = max( (int) $existing_uses->meta_value - $quantity, 0 );
			$existing_uses->save();
		} catch ( Exception $e ) {
			// Do nothing.
			return;
		}
	}
}

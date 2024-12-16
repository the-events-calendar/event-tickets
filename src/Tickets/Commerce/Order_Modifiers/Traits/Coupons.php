<?php
/**
 * Coupons trait.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Order_Modifier;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons as Coupons_Repository;

/**
 * Trait Coupons
 *
 * @since 5.18.0
 */
trait Coupons {

	/**
	 * The repository for interacting with the order modifiers table.
	 *
	 * @since 5.18.0
	 *
	 * @var Coupons_Repository
	 */
	protected Coupons_Repository $repo;

	/**
	 * Determine if a coupon is valid.
	 *
	 * @param string $slug The coupon slug.
	 *
	 * @return bool
	 */
	protected function is_coupon_slug_valid( string $slug ): bool {
		try {
			return $this->is_coupon_valid( $this->repo->find_by_slug( $slug ) );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Determine if a coupon is valid.
	 *
	 * @param Coupon $coupon The coupon to check.
	 *
	 * @return bool
	 */
	protected function is_coupon_valid( Coupon $coupon ) {
		try {
			// If it's not a coupon, it's invalid.
			if ( ! $this->is_coupon( $coupon ) ) {
				return false;
			}

			// If the status isn't active, the coupon is invalid.
			if ( $coupon->status !== 'active' ) {
				return false;
			}

			// If the coupon end date has passed, it is invalid.
			if ( null !== $coupon->end_date && strtotime( $coupon->end_date ) < current_time( 'timestamp' ) ) {
				return false;
			}

			// If the coupon start date is in the future, it is invalid.
			if ( null !== $coupon->start_date && strtotime( $coupon->start_date ) > current_time( 'timestamp' ) ) {
				return false;
			}

			// Check whether the coupon is still within its usage limit.
			// @todo: Implement this.

			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Determine if a modifier is a coupon.
	 *
	 * @param Order_Modifier $modifier The modifier to check.
	 *
	 * @return bool
	 */
	protected function is_coupon( Order_Modifier $modifier ): bool {
		return $modifier->modifier_type === 'coupon';
	}
}

<?php
/**
 * Cart trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Traits;

use TEC\Tickets\Commerce;

/**
 * Trait Cart
 *
 * @since TBD
 */
trait Cart {

	/**
	 * Get the name of the transient based on the cart ID.
	 *
	 * @param ?string $id The cart ID.
	 *
	 * @return string
	 */
	protected function get_transient_key( ?string $id ): string {
		return sprintf(
			'%s-cart-%s',
			Commerce::ABBR,
			md5( $id ?? '' )
		);
	}

	/**
	 * Get the expiration time for the cart transient.
	 *
	 * @since TBD
	 *
	 * @return int The expiration time in seconds.
	 */
	protected function get_transient_expiration(): int {
		/**
		 * Filters the expiration time for the cart transient.
		 *
		 * @since TBD
		 *
		 * @param int $expire The expiration time in seconds. Should be relative time, not an absolute timestamp.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_cart_transient_expiration', DAY_IN_SECONDS );
	}
}

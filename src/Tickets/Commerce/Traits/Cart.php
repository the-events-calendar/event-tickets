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
	 * @param string $id The cart ID.
	 *
	 * @return string
	 */
	protected function get_transient_key( string $id ) {
		return sprintf(
			'%s-cart-%s',
			Commerce::ABBR,
			md5( $id )
		);
	}
}

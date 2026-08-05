<?php
/**
 * Swaps the Commerce cart repository for RSVP_Cart when building TC-RSVP orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */

namespace TEC\Tickets\RSVP\V2\Cart;

use TEC\Tickets\Commerce\Cart\Cart_Interface;
use TEC\Tickets\RSVP\V2\Constants;

/**
 * Class Repository_Filter.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */
class Repository_Filter {

	/**
	 * Returns RSVP_Cart when the active order-from-cart context is TC-RSVP.
	 *
	 * Hooked onto `tec_tickets_commerce_cart_repository`, so the parameters are intentionally left
	 * untyped: other subscribers on the same filter could hand this a value that doesn't match the
	 * declared type, and a scalar/object type hint here would turn that into a fatal TypeError
	 * instead of a recoverable value.
	 *
	 * @since TBD Reads `$ticket_type` from the filter argument instead of a global var.
	 *
	 * @param Cart_Interface $cart        The default cart repository.
	 * @param string         $ticket_type The type of ticket the repository is being resolved for.
	 *
	 * @return Cart_Interface
	 */
	public function use_rsvp_cart_when_needed( $cart, $ticket_type = 'ticket' ) {
		if ( Constants::TC_RSVP_TYPE !== $ticket_type ) {
			return $cart;
		}

		return tribe( RSVP_Cart::class );
	}
}

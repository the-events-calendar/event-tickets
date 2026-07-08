<?php
/**
 * Swaps the Commerce cart repository for RSVP_Cart when building TC-RSVP orders.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Cart
 */

namespace TEC\Tickets\RSVP\V2\Cart;

use TEC\Tickets\Commerce\Cart;
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
	 * @since TBD
	 *
	 * @param Cart_Interface $cart The default cart repository.
	 *
	 * @return Cart_Interface
	 */
	public function use_rsvp_cart_when_needed( Cart_Interface $cart ): Cart_Interface {
		$ticket_type = tribe_get_var( Cart::ORDER_FROM_CART_TICKET_TYPE_VAR, 'ticket' );

		if ( Constants::TC_RSVP_TYPE !== $ticket_type ) {
			return $cart;
		}

		return tribe( RSVP_Cart::class );
	}
}

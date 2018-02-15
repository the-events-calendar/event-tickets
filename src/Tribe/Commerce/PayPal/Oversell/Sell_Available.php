<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Oversell__Sell_Available
 *
 * This policy allows selling what is available in inventory making a "best effort" toward the
 * fulfillment of the Order.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Oversell__Sell_Available extends Tribe__Tickets__Commerce__PayPal__Oversell__Policy {

	/**
	 * Whether this policy allows overselling or not.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function allows_overselling() {
		return true;
	}

	/**
	 * Modifies the quantity of tickets that can actually be over-sold according to
	 * this policy.
	 *
	 * @since TBD
	 *
	 * @param int $qty       The requested quantity
	 * @param int $inventory The current inventory value
	 *
	 * @return int The updated quantity
	 */
	public function modify_quantity( $qty, $inventory ) {
		return min( $inventory, $qty );
	}

	/**
	 * Returns the policy nice name.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Sell only available', 'event-tickets' );
	}
}
<?php
/**
 * Shared order-type helpers, used by order email senders and Flag Actions alike.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */

namespace TEC\Tickets\Commerce\Emails;

use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use WP_Post;

/**
 * Trait Order_Type_Trait.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Emails
 */
trait Order_Type_Trait {

	/**
	 * Determines whether an order is composed exclusively of TC-RSVP items.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The decorated order post object.
	 *
	 * @return bool
	 */
	protected function is_rsvp_order( WP_Post $order ): bool {
		if ( empty( $order->items ) || ! is_array( $order->items ) ) {
			return false;
		}

		foreach ( $order->items as $item ) {
			if ( RSVP_V2_Constants::TC_RSVP_TYPE !== ( $item['type'] ?? '' ) ) {
				return false;
			}
		}

		return true;
	}
}

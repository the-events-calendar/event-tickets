<?php
/**
 * Trait Not_Going_Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Traits
 */

namespace TEC\Tickets\RSVP\V2\Traits;

use TEC\Tickets\RSVP\V2\Constants;
use WP_Post;

/**
 * Trait Not_Going_Order.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Traits
 */
trait Not_Going_Order {
	/**
	 * Whether the given order is a hidden RSVP V2 order created for an attendee who
	 * indicated they will not be attending.
	 *
	 * These orders exist for tracking purposes only, so the generic Tickets Commerce
	 * order emails (purchase receipt, completed order, ticket delivery) should not be
	 * sent for them.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order The order post object, decorated with an `items` property.
	 *
	 * @return bool
	 */
	public function is_rsvp_v2_not_going_order( WP_Post $order ): bool {
		if ( empty( $order->items ) || ! is_array( $order->items ) ) {
			return false;
		}

		foreach ( $order->items as $item ) {
			if ( Constants::TC_RSVP_TYPE !== ( $item['type'] ?? '' ) ) {
				continue;
			}

			if ( ! tribe_is_truthy( $item['extra']['order_status'] ?? 'yes' ) ) {
				return true;
			}
		}

		return false;
	}
}

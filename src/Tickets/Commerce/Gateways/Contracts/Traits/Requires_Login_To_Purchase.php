<?php
/**
 * Tickets Commerce: Requires Login To Purchase Trait.
 *
 * @since 5.31.1.1
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts\Traits
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts\Traits;

use TEC\Tickets\Commerce\Order;
use WP_Error;

/**
 * Trait Requires_Login_To_Purchase.
 *
 * For the create-order REST endpoints only, so the permission check stays off the webhook and
 * onboarding endpoints that share the abstract endpoint.
 *
 * @since 5.31.1.1
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts\Traits
 */
trait Requires_Login_To_Purchase {

	/**
	 * Returns whether the current visitor may create an order, honoring the "Require users to log in
	 * before they purchase tickets" setting.
	 *
	 * Used as the permission callback of the gateways' create-order routes, so a refused request never
	 * reaches the handler that creates the order and its attendees.
	 *
	 * @since 5.31.1.1
	 *
	 * @return true|WP_Error True when the visitor may purchase, a WP_Error when they must log in first.
	 */
	public function can_purchase() {
		if ( ! tribe( Order::class )->must_login() ) {
			return true;
		}

		return new WP_Error(
			'tec_tickets_commerce_login_required',
			__( 'You must be logged in to purchase tickets.', 'event-tickets' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
}

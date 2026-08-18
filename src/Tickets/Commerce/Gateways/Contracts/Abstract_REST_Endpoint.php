<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Created;
use WP_REST_Request;

/**
 * Abstract REST Endpoint Contract
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_REST_Endpoint implements REST_Endpoint_Interface, \Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * The Pending_Order instance.
	 *
	 * @since 5.29.0.1
	 *
	 * @var Pending_Order
	 */
	protected Pending_Order $pending_order;

	/**
	 * The Cart instance.
	 *
	 * @since 5.29.0.1
	 *
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * @since 5.29.0.1
	 *
	 * @param Pending_Order $pending_order The Pending_Order instance.
	 * @param Cart          $cart          The Cart instance.
	 */
	public function __construct( Pending_Order $pending_order, Cart $cart ) {
		$this->pending_order = $pending_order;
		$this->cart          = $cart;
	}

	/**
	 * @inheritDoc
	 */
	public function get_endpoint_path() {
		return $this->path;
	}

	/**
	 * @inheritDoc
	 */
	public function get_route_url() {
		$namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$scheme    = Settings::is_test_mode() ? 'rest' : 'https';

		return rest_url( '/' . $namespace . $this->get_endpoint_path(), $scheme );
	}

	/**
	 * Gets the Return URL pointing to this on boarding route.
	 *
	 * @since 5.3.0 moved to Abstract_REST_Endpoint
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_return_url( $hash = null ) {
		$arguments = [
			'hash' => $hash,
		];

		return add_query_arg( $arguments, $this->get_route_url() );
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since 5.3.0 moved to Abstract_REST_Endpoint
	 * @since 5.1.9
	 *
	 * @param mixed $value Value of the 'filter' argument.
	 *
	 * @return string|array
	 */
	public function sanitize_callback( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @TODO  We need to make sure Swagger documentation is present.
	 *
	 * @since 5.3.0 moved to Abstract_REST_Endpoint
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function get_documentation() {
		return [];
	}

	/**
	 * Ensures that the current request tries to edit an order id which is stored as pending edit.
	 *
	 * The cart-bound check is the preferred path. It depends on the visitor's cart cookie surviving the
	 * round trip to the gateway, which is not guaranteed on every host: edge caches, proxies and
	 * hostname mismatches can all drop it. When that happens the buyer has already been charged, so
	 * gateways may authorize the request instead with a credential they issued for that specific order.
	 *
	 * @since 5.29.0.1
	 * @since 5.29.3 Falls back to a gateway-issued, order-scoped credential when the cart cookie is gone.
	 *
	 * @param WP_REST_Request $request The REST Request instance.
	 *
	 * @return bool
	 */
	public function current_user_can_edit_order( WP_REST_Request $request ): bool {
		$order_id = $request->get_param( 'order_id' );

		if ( ! $order_id ) {
			return false;
		}

		if ( $this->cart_owns_pending_order( (string) $order_id ) ) {
			return true;
		}

		return $this->request_carries_order_credential( $request, (string) $order_id );
	}

	/**
	 * Whether the current visitor's cart is the one that registered the given gateway order id as its
	 * pending order.
	 *
	 * The order id must match the pending order stored for the cart hash read from the visitor's
	 * cookie, and a Created/Pending order carrying that same cart hash must exist.
	 *
	 * @since 5.29.3
	 *
	 * @param string $gateway_order_id The gateway's order id.
	 *
	 * @return bool
	 */
	protected function cart_owns_pending_order( string $gateway_order_id ): bool {
		if ( $gateway_order_id !== $this->pending_order->get() ) {
			return false;
		}

		// Reaching here means the pending order resolved, which already required a cart hash.
		$existing_order = tec_tc_orders()->by_args(
			[
				'gateway_order_id' => $gateway_order_id,
				'hash'             => $this->cart->get_cart_hash( false ),
				'status'           => 'any',
			]
		)->first();

		return $this->order_is_in_flight( $existing_order );
	}

	/**
	 * Whether an order exists and is still in flight, so a gateway request may drive it to a final
	 * status.
	 *
	 * The status is checked here, on the fetched post, rather than handed to the query, because the
	 * orders ORM does neither of the things a status argument looks like it does. A list of statuses
	 * produces no post_status clause at all, so it filters nothing; omitting the argument instead falls
	 * back to the repository's default post_status, which is the insert status alone. Querying for
	 * `any` and deciding here is the only combination that means what it says.
	 *
	 * @since 5.29.3
	 *
	 * @param mixed $order The order post, or anything falsy when no order was found.
	 *
	 * @return bool
	 */
	protected function order_is_in_flight( $order ): bool {
		if ( empty( $order->ID ) ) {
			return false;
		}

		$in_flight = [
			tribe( Created::class )->get_wp_slug(),
			tribe( Pending::class )->get_wp_slug(),
		];

		return in_array( $order->post_status, $in_flight, true );
	}

	/**
	 * Whether the request carries a credential this gateway issued for the given order.
	 *
	 * Gateways that hand the buyer an order-scoped secret when the order is created can override this
	 * to authorize the request on that secret alone, which keeps checkout working when the cart cookie
	 * did not survive. Gateways with no such secret keep the cart-bound check as their only path.
	 *
	 * A gateway overriding this MUST compare against a value it stored for that specific order, using
	 * a timing-safe comparison. Accepting a merely well-formed value would reduce this gate to an
	 * existence check on the order id.
	 *
	 * @since 5.29.3
	 *
	 * @param WP_REST_Request $request          The REST Request instance.
	 * @param string          $gateway_order_id The gateway's order id.
	 *
	 * @return bool
	 */
	protected function request_carries_order_credential( WP_REST_Request $request, string $gateway_order_id ): bool {
		return false;
	}
}

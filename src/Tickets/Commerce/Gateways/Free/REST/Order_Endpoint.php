<?php
/**
 * Tickets Commerce: Free Gateway Order Endpoint.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\Gateways\Free\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class Order Endpoint.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.10.0
	 *
	 * @var string
	 */
	protected $path = '/commerce/free/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.10.0
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_order' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Free gateway.
	 *
	 * @since 5.10.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$data      = $request->get_json_params();
		$purchaser = tribe( Order::class )->get_purchaser_data( $data );

		if ( is_wp_error( $purchaser ) ) {
			return $purchaser;
		}

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		
		$created = tribe( Order::class )->modify_status(
			$order->ID,
			Pending::SLUG,
		);

		if ( is_wp_error( $created ) ) {
			return $created;
		}

		$updated = tribe( Order::class )->modify_status(
			$order->ID,
			Completed::SLUG,
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		tribe( Cart::class )->clear_cart();

		$response['success']      = true;
		$response['id']           = $order->ID;
		$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $order->gateway_order_id ], tribe( Success::class )->get_url() );

		return new WP_REST_Response( $response );
	}
}

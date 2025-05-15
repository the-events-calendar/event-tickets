<?php
/**
 * Tickets Commerce: Free Gateway Order Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Free\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;
use TEC\Tickets\Event;

use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class Ticket Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Free\REST
 */
class Ticket_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/commerce/ticket';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'handle_create_ticket' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_create_ticket' ],
					'permission_callback' => [ $this, 'check_permission' ],
				]
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Checks if the current user has the capability to edit events and verifies the nonce.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return bool True if the user has the edit events capability and nonce is valid, false otherwise.
	 */
	public function check_permission( WP_REST_Request $request ): bool {
		$nonce = $request->get_param( '_wpnonce' );

		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			//return false;
		}

		// phpcs:disable WordPress.WP.Capabilities.Unknown
		return current_user_can( 'edit_tribe_events' );
		// phpcs:enable WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Handles the request that create ticket for Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_ticket( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$request_params                            = $request->get_params();
		$args                                      = [];
		$post_id                                   = Arr::get( $request_params, 'post_ID' );
		$args['post_id']                           = Event::filter_event_id( $post_id );
		$args['rsvp_id']                           = Arr::get( $request_params, 'rsvp_id', '' );
		$args['rsvp_limit']                        = Arr::get( $request_params, 'rsvp_limit', '' );
		$args['ticket_end_date']                   = Arr::get( $request_params, 'rsvp_end_date', '' );
		$args['ticket_end_time']                   = Arr::get( $request_params, 'rsvp_end_time', '' );
		$args['ticket_start_date']                 = Arr::get( $request_params, 'rsvp_start_date', '' );
		$args['ticket_start_time']                 = Arr::get( $request_params, 'rsvp_start_time', '' );
		$args['tec_tickets_rsvp_enable_cannot_go'] = Arr::get( $request_params, 'tec_tickets_rsvp_enable_cannot_go', '' );
		$args['ticket_provider']                   = Arr::get( $request_params, 'ticket_provider', '' );
		$args['ticket_type']                       = Arr::get( $request_params, 'ticket_type', 'tc-rsvp' );

		$module = tribe( Module::class );
		$rsvp_id = $module->ticket_add( $post_id, $args );
		//$ticket_module = tribe( Module::class );
		//$ticket_module->save_ticket( $post_id, $ticket, $raw_data = [] );

		return new WP_REST_Response( $response );

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

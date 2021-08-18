<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use tad\WPBrowser\Adapters\WP;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Refresh_Token;

use TEC\Tickets\Commerce\Gateways\PayPal\Signup;
use TEC\Tickets\Commerce\Gateways\PayPal\WhoDat;


use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use Tribe__Documentation__Swagger__Provider_Interface;
use Tribe__Settings;
use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class Order Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class Order_Endpoint implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/paypal/order';

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
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_create_order' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '(?P<id>\d+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->update_order_args(),
				'callback'            => [ $this, 'handle_update_order' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Gets the Endpoint path for the on boarding process.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_endpoint_path() {
		return $this->path;
	}

	/**
	 * Get the REST API route URL.
	 *
	 * @since TBD
	 *
	 * @return string The REST API route URL.
	 */
	public function get_route_url() {
		$namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		return rest_url( '/' . $namespace . $this->get_endpoint_path(), 'https' );
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ) );

		$unit = [
			'reference_id' => $order->ID,
			'value'        => $order->total_value,
			'currency'     => $order->currency,
			'first_name'   => 'Gustavo',
			'last_name'    => 'Bordoni',
			'email'        => 'gustavo@bordoni.me',
		];

		$paypal_order = tribe( Client::class )->create_order( $unit );

		if ( empty( $paypal_order['id'] ) || empty( $paypal_order['create_time'] ) ) {
			return new WP_Error( 'tec-tc-gateway-paypal-failed-creating-order', null, $order );
		}

		$updated = tec_tc_orders()->by_args( [
			'status' => tribe( Created::class )->get_wp_slug(),
			'id'     => $order->ID,
		] )->set_args( [
			'gateway_payload'  => $paypal_order,
			'gateway_order_id' => $paypal_order['id'],
		] )->save();

		tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		// Respond with the ID for Paypal Usage.
		$response['id'] = $paypal_order['id'];

		return new WP_REST_Response( $response );
	}

	/**
	 * Handles the request that updates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_update_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];


		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the signup redirect.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function create_order_args() {
		// Webhooks do not send any arguments, only JSON content.
		return [
			'accountStatus' => [
				'description'       => 'The merchant ID in PayPal',
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The accountStatus argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Arguments used for the signup redirect.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function update_order_args() {
		// Webhooks do not send any arguments, only JSON content.
		return [
			'accountStatus' => [
				'description'       => 'The merchant ID in PayPal',
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The accountStatus argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_documentation() {
		return [];
	}
}

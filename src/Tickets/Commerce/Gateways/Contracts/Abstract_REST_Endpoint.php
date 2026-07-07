<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce\Pending_Order;
use TEC\Tickets\Commerce\Settings;
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
	 * @since TBD
	 *
	 * @var Pending_Order
	 */
	protected Pending_Order $pending_order;

	/**
	 * @since TBD
	 *
	 * @param Pending_Order $pending_order The Pending_Order instance.
	 */
	public function __construct( Pending_Order $pending_order ) {
		$this->pending_order = $pending_order;
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
	 * @since TBD
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

		return $order_id === $this->pending_order->get();
	}
}

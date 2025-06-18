<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Hooks.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.10.0
	 */
	public function register() {
		$this->add_filters();
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.10.0
	 * @since 5.24.0 Added the filter to add a gateway order ID for the free gateway.
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_filter( 'tec_tickets_commerce_order_' . Gateway::get_key() . '_create_args', [ $this, 'add_free_gateway_id' ] );
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @since 5.10.0
	 *
	 * @param array $gateways List of available gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}

	/**
	 * Register the REST API endpoints.
	 *
	 * @since 5.10.0
	 */
	public function register_endpoints() {
		$this->container->make( REST\Order_Endpoint::class )->register();
	}

	/**
	 * Produce a gateway order ID for the free gateway.
	 *
	 * @since 5.24.0
	 *
	 * @param array $args The arguments to create the order.
	 *
	 * @return array The arguments to create the order.
	 */
	public function add_free_gateway_id( $args ) {
		$args['gateway_order_id'] = md5( wp_generate_password() . microtime() );

		return $args;
	}
}

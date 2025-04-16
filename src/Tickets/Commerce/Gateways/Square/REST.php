<?php
/**
 * REST API for Square.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;

/**
 * Class REST
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class REST extends Controller_Contract {
	/**
	 * The on boarding endpoint.
	 *
	 * @since TBD
	 *
	 * @var On_Boarding_Endpoint
	 */
	protected On_Boarding_Endpoint $on_boarding_endpoint;

	/**
	 * The order endpoint.
	 *
	 * @since TBD
	 *
	 * @var Order_Endpoint
	 */
	protected Order_Endpoint $order_endpoint;

	/**
	 * REST constructor.
	 *
	 * @since TBD
	 *
	 * @param Container            $container The container instance.
	 * @param On_Boarding_Endpoint $on_boarding_endpoint The on boarding endpoint instance.
	 * @param Order_Endpoint       $order_endpoint The order endpoint instance.
	 */
	public function __construct( Container $container, On_Boarding_Endpoint $on_boarding_endpoint, Order_Endpoint $order_endpoint ) {
		parent::__construct( $container );

		$this->on_boarding_endpoint = $on_boarding_endpoint;
		$this->order_endpoint       = $order_endpoint;
	}

	/**
	 * Register the REST API endpoint classes in the container.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Registers the REST API endpoints for Square.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->on_boarding_endpoint->register();
		$this->order_endpoint->register();
	}
}

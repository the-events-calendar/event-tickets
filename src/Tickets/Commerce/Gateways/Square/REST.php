<?php
/**
 * REST API for Square.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Webhook_Endpoint;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;

/**
 * Class REST
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class REST extends Controller_Contract {
	/**
	 * The on boarding endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @var On_Boarding_Endpoint
	 */
	protected On_Boarding_Endpoint $on_boarding_endpoint;

	/**
	 * The order endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @var Order_Endpoint
	 */
	protected Order_Endpoint $order_endpoint;

	/**
	 * The webhook endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @var Webhook_Endpoint
	 */
	protected Webhook_Endpoint $webhook_endpoint;

	/**
	 * REST constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param Container            $container The container instance.
	 * @param On_Boarding_Endpoint $on_boarding_endpoint The on boarding endpoint instance.
	 * @param Order_Endpoint       $order_endpoint The order endpoint instance.
	 * @param Webhook_Endpoint     $webhook_endpoint The webhook endpoint instance.
	 */
	public function __construct(
		Container $container,
		On_Boarding_Endpoint $on_boarding_endpoint,
		Order_Endpoint $order_endpoint,
		Webhook_Endpoint $webhook_endpoint
	) {
		parent::__construct( $container );

		$this->on_boarding_endpoint = $on_boarding_endpoint;
		$this->order_endpoint       = $order_endpoint;
		$this->webhook_endpoint     = $webhook_endpoint;
	}

	/**
	 * Register the REST API endpoint classes in the container.
	 *
	 * @since 5.24.0
	 */
	public function do_register(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Registers the REST API endpoints for Square.
	 *
	 * @since 5.24.0
	 */
	public function register_endpoints() {
		$this->on_boarding_endpoint->register();
		$this->order_endpoint->register();
		$this->webhook_endpoint->register();
	}
}

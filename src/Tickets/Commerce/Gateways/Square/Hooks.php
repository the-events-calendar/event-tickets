<?php
/**
 * Square Generic Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;

use Tribe__Repository;

/**
 * Square Hooks class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Hooks extends Controller_Contract {
	/**
	 * Gateway instance.
	 *
	 * @since TBD
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Ajax constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container Container instance.
	 * @param Gateway   $gateway Gateway instance.
	 */
	public function __construct( Container $container, Gateway $gateway ) {
		parent::__construct( $container );
		$this->gateway = $gateway;
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		add_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ], 10, 2 );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ] );
		remove_filter( 'tec_repository_schema_tc_orders', [ $this, 'filter_orders_repository_schema' ], 10 );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since TBD
	 *
	 * @param array             $schema     The schema.
	 * @param Tribe__Repository $repository The repository.
	 *
	 * @return array
	 */
	public function filter_orders_repository_schema( array $schema = [], ?Tribe__Repository $repository = null ) {
		return tribe( Order::class )->filter_schema( $schema, $repository );
	}

	/**
	 * Filter the Commerce Gateways to add Square.
	 *
	 * @since TBD
	 *
	 * @param array $gateways List of gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		$gateways[ Gateway::get_key() ] = $this->gateway;

		return $gateways;
	}
}

<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * @since TBD
	 * @return mixed
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @since TBD
	 *
	 * @param array $gateways List of available gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}

	/**
	 * Register the Endpoints from Stripe.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( REST::class )->register_endpoints();
	}

}
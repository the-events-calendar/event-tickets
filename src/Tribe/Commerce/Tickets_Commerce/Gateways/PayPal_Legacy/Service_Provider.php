<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy;

use tad_DI52_ServiceProvider;

/**
 * Service provider for the Tickets Commerce: PayPal Standard (Legacy) gateway.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy
 */
class Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( Gateway::class );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_filter( 'tribe_tickets_commerce_paypal_gateways', $this->container->callback( Gateway::class, 'register_gateway' ), 11, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', $this->container->callback( Gateway::class, 'is_active' ), 9, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_should_show_paypal_legacy', $this->container->callback( Gateway::class, 'should_show' ), 9, 2 );
	}

}

<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Notice_Handler;

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
		add_action( 'plugins_loaded', [ $this, 'handle_action_connected' ] );
		add_action( 'admin_init', [ $this, 'handle_stripe_errors' ] );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_notice_messages', [ $this, 'include_admin_notices' ] );
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

	/**
	 * Receive data after a connection to stripe has been established
	 *
	 * @since TBD
	 */
	public function handle_action_connected() {

		if ( empty( $_GET['stripe'] ) ) {
			return;
		}

		tribe( Signup::class )->handle_connection_established();
	}

	public function handle_stripe_errors() {

		if ( empty( $_GET['tc-stripe-error'] ) ) {
			return;
		}

		tribe( Notice_Handler::class )->trigger_admin( $_GET[ 'tc-stripe-error' ] );

	}

	/**
	 * Include Stripe admin notices for Ticket Commerce.
	 *
	 * @since TBD
	 *
	 * @param array $messages Array of messages.
	 *
	 * @return array
	 */
	public function include_admin_notices( $messages ) {
		return array_merge( $messages, $this->container->make( Gateway::class )->get_admin_notices() );
	}
}
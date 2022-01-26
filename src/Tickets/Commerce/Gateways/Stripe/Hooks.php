<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Notice_Handler;

/**
 * Class Hooks
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Stripe component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'plugins_loaded', [ $this, 'handle_action_connected' ] );
		add_action( 'tribe_template_after_include:tickets/v2/commerce/checkout/footer', [ $this, 'include_payment_buttons' ], 15, 3 );

		add_action( 'admin_init', [ $this, 'handle_stripe_errors' ] );
	}

	/**
	 * Adds the filters required by each Stripe component.
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

		if ( empty( tribe_get_request_var( 'stripe' ) ) ) {
			return;
		}

		tribe( Signup::class )->handle_connection_established();
	}

	/**
	 * Handle stripe errors into the admin UI.
	 *
	 * @since TBD
	 */
	public function handle_stripe_errors() {

		if ( empty( tribe_get_request_var( 'tc-stripe-error' ) ) ) {
			return;
		}

		tribe( Notice_Handler::class )->trigger_admin( tribe_get_request_var( 'tc-stripe-error' ) );
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

	/**
	 * Include the payment element from Stripe into the Checkout page.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 */
	public function include_payment_buttons( $file, $name, $template ) {
		$this->container->make( Payment_Element::class )->include_payment_element( $file, $name, $template );
	}
}
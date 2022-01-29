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
		add_action( 'wp', [ $this, 'handle_action_stripe_return' ] );
		add_action( 'wp', [ $this, 'maybe_create_stripe_payment_intent' ] );
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
	 * Receive data after a connection to stripe has been attempted
	 *
	 * @since TBD
	 */
	public function handle_action_stripe_return() {

		$query_args = tribe_get_request_var( 'stripe' );

		if ( empty( $query_args ) || ( defined('REST_REQUEST') && REST_REQUEST ) ) {
			return;
		}

		$url = add_query_arg(
			[ 'stripe' => $query_args ],
			rest_url( tribe( Signup::class )->signup_return_path )
		);

		wp_safe_redirect( $url );
		exit();
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
		$this->container->make( Stripe_Elements::class )->include_form( $file, $name, $template );
	}

	/**
	 * Checks if Stripe is active and can be used to check out in the current cart and, if so,
	 * generates a payment intent
	 *
	 * @since TBD
	 */
	public function maybe_create_stripe_payment_intent() {

		if ( ! tribe( Merchant::class )->is_active() || ! tribe( Merchant::class )->is_connected() ) {
			return;
		}

		tribe( Client::class )->create_payment_intent();
	}
}
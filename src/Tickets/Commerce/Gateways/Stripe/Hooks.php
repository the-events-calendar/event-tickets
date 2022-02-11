<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Notice_Handler;

/**
 * Class Hooks
 *
 * @since   TBD
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
		add_action( 'wp', [ $this, 'maybe_create_stripe_payment_intent' ] );

		add_action( 'admin_init', [ $this, 'handle_stripe_errors' ] );

		add_action( 'wp_ajax_tec_tickets_commerce_gateway_stripe_test_webhooks', [ $this, 'action_handle_testing_webhooks_field' ] );
	}

	/**
	 * Adds the filters required by each Stripe component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 5, 2 );
		add_filter( 'tec_tickets_commerce_notice_messages', [ $this, 'include_admin_notices' ] );
		add_filter( 'tribe_field_div_end', [ $this, 'filter_include_webhooks_copy' ], 10, 2 );
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
	public function filter_add_gateway( array $gateways = [] ) : array {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}

	/**
	 * Modify the HTML of the Webhooks field to include a copy button.
	 *
	 * @since TBD
	 *
	 * @param string        $html
	 * @param \Tribe__Field $field
	 *
	 * @return string
	 */
	public function filter_include_webhooks_copy( string $html, \Tribe__Field $field ) : string {
		return $this->container->make( Webhooks::class )->include_webhooks_copy_button( $html, $field );
	}

	/**
	 * Register the Endpoints from Stripe.
	 *
	 * @since TBD
	 */
	public function register_endpoints() : void {
		$this->container->make( REST::class )->register_endpoints();
	}

	/**
	 * Handles the validation of the signing key on the settings page.
	 *
	 * @since TBD
	 */
	public function action_handle_testing_webhooks_field() : void {
		$this->container->make( Webhooks::class )->handle_validation();
	}

	/**
	 * Handle stripe errors into the admin UI.
	 *
	 * @since TBD
	 */
	public function handle_stripe_errors() {

		$merchant_denied = tribe( Merchant::class )->is_merchant_unauthorized();

		if ( $merchant_denied ) {
			return tribe( Notice_Handler::class )->trigger_admin( $merchant_denied );
		}

		if ( empty( tribe_get_request_var( 'tc-stripe-error' ) ) ) {
			return;
		}

		return tribe( Notice_Handler::class )->trigger_admin( tribe_get_request_var( 'tc-stripe-error' ) );
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
	 * Checks if Stripe is active and can be used to check out in the current cart and, if so,
	 * generates a payment intent
	 *
	 * @since TBD
	 */
	public function maybe_create_stripe_payment_intent() {

		if ( ! tribe( Merchant::class )->is_connected() || ! tribe( Module::class )->is_checkout_page() ) {
			return;
		}

		tribe( Client::class )->create_payment_intent();
	}
}
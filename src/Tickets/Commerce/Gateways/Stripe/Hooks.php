<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Notice_Handler;

/**
 * Class Hooks
 *
 * @since   5.3.0
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
	 * @since 5.3.0
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
	 * @since 5.3.0
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 5, 2 );
		add_filter( 'tec_tickets_commerce_notice_messages', [ $this, 'include_admin_notices' ] );
		add_filter( 'tec_tickets_commerce_stripe_settings', [ $this, 'include_webhook_settings' ], 20 );
		add_filter( 'tribe_field_div_end', [ $this, 'filter_include_webhooks_copy' ], 10, 2 );
		add_filter( 'tribe_settings_save_field_value', [ $this, 'validate_payment_methods' ], 10, 2 );
		add_filter( 'tribe_settings_validate_field_value', [ $this, 'provide_defaults_for_hidden_fields'], 10, 3 );
		add_filter( 'tec_tickets_commerce_admin_notices', [ $this, 'filter_admin_notices' ] );
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @since 5.3.0
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
	 * @since 5.3.0
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
	 * @since 5.3.0
	 */
	public function register_endpoints() : void {
		$this->container->make( REST::class )->register_endpoints();
	}

	/**
	 * Handles the validation of the signing key on the settings page.
	 *
	 * @since 5.3.0
	 */
	public function action_handle_testing_webhooks_field() : void {
		$this->container->make( Webhooks::class )->handle_validation();
	}

	/**
	 * Handle stripe errors into the admin UI.
	 *
	 * @since 5.3.0
	 */
	public function handle_stripe_errors() {

		$merchant_denied = tribe( Merchant::class )->is_merchant_unauthorized();

		if ( $merchant_denied ) {
			return tribe( Notice_Handler::class )->trigger_admin( $merchant_denied );
		}

		$merchant_disconnected = tribe( Merchant::class )->is_merchant_deauthorized();

		if ( $merchant_disconnected ) {
			return tribe( Notice_Handler::class )->trigger_admin( $merchant_disconnected );
		}

		tribe( Settings::class )->alert_currency_mismatch();

		if ( empty( tribe_get_request_var( 'tc-stripe-error' ) ) ) {
			return;
		}

		return tribe( Notice_Handler::class )->trigger_admin( tribe_get_request_var( 'tc-stripe-error' ) );
	}

	/**
	 * Include Stripe admin notices for Ticket Commerce.
	 *
	 * @since 5.3.0
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
	 * @since 5.3.0
	 */
	public function maybe_create_stripe_payment_intent() {

		if ( ! tribe( Merchant::class )->is_connected() || ! tribe( Module::class )->is_checkout_page() ) {
			return;
		}

		tribe( Payment_Intent_Handler::class )->create_payment_intent_for_cart();
	}

	/**
	 * Intercept saving settings to check if any new payment methods would break Stripe payment intents.
	 *
	 * @since 5.3.0
	 *
	 * @param mixed  $value    The new value.
	 * @param string $field_id The field id in the options.
	 *
	 * @return mixed
	 */
	public function validate_payment_methods( $value, $field_id ) {

		if ( $field_id !== Settings::$option_checkout_element_payment_methods ) {
			return $value;
		}

		return Payment_Intent::validate_payment_methods( $value, $field_id );
	}

	/**
	 * Add Webhook settings fields
	 *
	 * @since 5.3.0
	 *
	 * @param array $settings Array of settings for the Stripe gateway.
	 *
	 * @return mixed
	 */
	public function include_webhook_settings( $settings ) {
		if ( ! tribe( Merchant::class )->is_connected() ) {
			return $settings;
		}

		return array_merge( $settings, tribe( Webhooks::class )->get_fields() );
	}

	/**
	 * Makes sure mandatory fields have values when hidden.
	 *
	 * @since 5.3.0
	 *
	 * @param mixed  $value    Field value submitted.
	 * @param string $field_id Field key in the settings array.
	 * @param array  $field    Entire field array.
	 *
	 * @return mixed
	 */
	public function provide_defaults_for_hidden_fields( $value, $field_id, $field ) {
		return tribe( Settings::class )->reset_hidden_field_values( $value, $field_id, $field );
	}

	/**
	 * Filter admin notices.
	 *
	 * @since 5.3.2
	 *
	 * @param array $notices Array of admin notices.
	 *
	 * @return array
	 */
	public function filter_admin_notices( $notices ) {
		return $this->container->make( Gateway::class )->filter_admin_notices( $notices );
	}
}
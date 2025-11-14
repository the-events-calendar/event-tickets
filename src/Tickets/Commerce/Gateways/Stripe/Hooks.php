<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Notice_Handler;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Success;
use Tribe\Tickets\Admin\Settings as Admin_Settings;
use Tribe\Admin\Pages;
use Tribe__Tickets__Main as Tickets_Plugin;
use WP_Post;
use Exception;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use Tribe__Utils__Array as Arr;

/**
 * Class Hooks
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

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
	 * @since 5.24.0 Moved async webhook process to Commerce Hooks routing action.
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'tec_tickets_commerce_checkout_page_parse_request', [ $this, 'maybe_create_stripe_payment_intent' ], 10000 );

		add_action( 'admin_init', [ $this, 'handle_stripe_errors' ] );
		// Set up during feature release.
		add_action( 'admin_init', [ $this, 'setup_stripe_webhook_on_release' ] );
		// Set up during plugin activation.
		add_action( 'admin_init', [ $this, 'setup_stripe_webhook_on_activation' ] );

		add_action( 'tec_tickets_commerce_checkout_page_parse_request', [ $this, 'handle_checkout_request' ] );

		add_action( 'wp_ajax_tec_tickets_commerce_gateway_stripe_test_webhooks', [ $this, 'action_handle_testing_webhooks_field' ] );
		add_action( 'wp_ajax_tec_tickets_commerce_gateway_stripe_verify_webhooks', [ $this, 'action_handle_verify_webhooks' ] );

		add_action( 'wp_ajax_' . Webhooks::NONCE_KEY_SETUP, [ $this, 'action_handle_set_up_webhook' ] );
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
		add_filter( 'tec_tickets_commerce_success_page_should_display_billing_fields', [ $this, 'modify_checkout_display_billing_info' ] );
		add_filter( 'tec_tickets_commerce_shortcode_checkout_page_template_vars', [ $this, 'modify_checkout_vars' ] );
		add_filter( 'tec_tickets_commerce_order_stripe_get_value_refunded', [ $this, 'filter_order_get_value_refunded' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_order_stripe_get_value_captured', [ $this, 'filter_order_get_value_captured' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', [ $this, 'filter_stripe_currency_precision' ], 10, 3 );
	}

	/**
	 * Filter the refunded amount for the order.
	 *
	 * @since 5.24.0
	 *
	 * @param ?int  $nothing The current value.
	 * @param array $refunds The refunds for the order.
	 *
	 * @return int
	 */
	public function filter_order_get_value_refunded( ?int $nothing, array $refunds ): int {
		if ( $nothing ) {
			return $nothing;
		}

		if ( empty( $refunds['0']['amount_refunded'] ) ) {
			return 0;
		}

		return (int) max( wp_list_pluck( $refunds, 'amount_refunded' ) );
	}

	/**
	 * Filter the captured amount for the order.
	 *
	 * @since 5.24.0
	 *
	 * @param ?int  $nothing The current value.
	 * @param array $refunds The refunds for the order.
	 *
	 * @return int
	 */
	public function filter_order_get_value_captured( ?int $nothing, array $refunds ): int {
		if ( $nothing ) {
			return $nothing;
		}

		if ( empty( $refunds['0']['amount_captured'] ) ) {
			return 0;
		}

		return (int) max( wp_list_pluck( $refunds, 'amount_captured' ) );
	}

	/**
	 * Process the async stripe webhook.
	 *
	 * @since 5.18.1
	 * @since 5.19.3 Added the $retry parameter.
	 *
	 * @param int $order_id The order ID.
	 * @param int $retry      The number of times this has been tried.
	 *
	 * @throws Exception If the action fails after too many retries.
	 */
	public function process_async_stripe_webhook( int $order_id, int $retry = 0 ): void {
		$order = tec_tc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( ! $order instanceof WP_Post ) {
			return;
		}

		if ( ! $order->ID ) {
			return;
		}

		$webhooks = tribe( Webhooks::class );

		if ( time() < $order->on_checkout_hold ) {
			if ( $retry > $webhooks->get_max_number_of_retries() ) {
				throw new Exception( __( 'Failed to process the webhook after too many tries.', 'event-tickets' ) );
			}

			as_schedule_single_action(
				$order->on_checkout_hold + MINUTE_IN_SECONDS,
				'tec_tickets_commerce_async_webhook_process',
				[
					'order_id' => $order_id,
					'try'      => ++$retry,
				],
				'tec-tickets-commerce-webhooks'
			);
			return;
		}

		$pending_webhooks = $webhooks->get_pending_webhooks( $order->ID );

		// On multiple checkout completes, make sure we dont process the same webhook twice.
		$webhooks->delete_pending_webhooks( $order->ID );

		foreach ( $pending_webhooks as $pending_webhook ) {
			if ( ! ( is_array( $pending_webhook ) ) ) {
				continue;
			}

			if ( ! isset( $pending_webhook['new_status'], $pending_webhook['metadata'], $pending_webhook['old_status'] ) ) {
				continue;
			}

			$new_status_wp_slug = $pending_webhook['new_status'];

			// The order is already there!
			if ( $order->post_status === $new_status_wp_slug ) {
				continue;
			}

			// The order is no longer where it was... that could be dangerous, lets bail?
			if ( $order->post_status !== $pending_webhook['old_status'] ) {
				continue;
			}

			tribe( Order::class )->modify_status(
				$order->ID,
				tribe( Status_Handler::class )->get_by_wp_slug( $new_status_wp_slug )->get_slug(),
				$pending_webhook['metadata']
			);
		}
	}

	/**
	 * Set up Stripe Webhook based on transient value.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function setup_stripe_webhook_on_activation() {
		/**
		 * Filters whether to enable the Stripe Webhook.
		 *
		 * @since 5.11.0
		 *
		 * @param bool $need_to_enable_stripe_webhook Whether to enable the Stripe Webhook.
		 */
		$need_to_enable_stripe_webhook = apply_filters( 'tec_tickets_commerce_need_to_enable_stripe_webhook', get_transient( 'tec_tickets_commerce_setup_stripe_webhook' ) );

		if ( false === $need_to_enable_stripe_webhook ) {
			return false;
		}

		// Always delete the transient.
		delete_transient( 'tec_tickets_commerce_setup_stripe_webhook' );

		// Bail on non-truthy values as well.
		if ( ! tribe_is_truthy( $need_to_enable_stripe_webhook ) ) {
			return false;
		}

		return tribe( Webhooks::class )->handle_webhook_setup();
	}

	/**
	 * Set up Stripe Webhook based on the plugin version.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function setup_stripe_webhook_on_release() {
		$stripe_webhook_version = tribe_get_option( 'tec_tickets_commerce_stripe_webhook_version', false );

		if ( $stripe_webhook_version ) {
			return false;
		}

		tribe_update_option( 'tec_tickets_commerce_stripe_webhook_version', Tickets_Plugin::VERSION );

		return tribe( Webhooks::class )->handle_webhook_setup();
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
	 * Handles the testing of the signing key on the settings page.
	 *
	 * @since 5.3.0
	 */
	public function action_handle_testing_webhooks_field() : void {
		$this->container->make( Webhooks::class )->handle_validation();
	}

	/**
	 * Handles the validation of the signing key on the settings page.
	 *
	 * @since 5.5.6
	 */
	public function action_handle_verify_webhooks() : void {
		$this->container->make( Webhooks::class )->handle_verification();
	}

	/**
	 * Handles the setting up of the webhook on the settings page.
	 *
	 * @since 5.11.0
	 *
	 * @return void
	 */
	public function action_handle_set_up_webhook(): void {
		$nonce  = tribe_get_request_var( 'tc_nonce' );
		$status = esc_html__( 'Something went wrong with your Webhook Creation. Please reload the page and try again later.', 'event-tickets' );

		$webhooks = $this->container->make( Webhooks::class );

		if ( ! wp_verify_nonce( $nonce, Webhooks::NONCE_KEY_SETUP ) || ! current_user_can( Pages::get_capability() ) ) {
			wp_send_json_error( [ 'status' => $status ] );
			return;
		}

		$result = $webhooks->handle_webhook_setup();

		if ( ! $result ) {
			wp_send_json_error( [ 'status' => $status ] );
			return;
		}

		wp_send_json_success( [ 'status' => esc_html__( 'Webhook successfully set up! The page will reload now.', 'event-tickets' ) ] );
	}

	/**
	 * Handle Stripe errors into the admin UI.
	 *
	 * @since 5.3.0
	 * @since 5.6.3   Added check for ajax call, and additional logic to only run logic on checkout page and when Stripe is connected.
	 */
	public function handle_stripe_errors() {

		// Bail out if not on Stripe Settings Page or TicketsCommerce Checkout page.
		if ( ! tribe( Admin_Settings::class )->is_on_tab_section( 'payments', 'stripe' ) || ! tribe( Module::class )->is_checkout_page() ) {
			return;
		}

		// Bail if this is an ajax call.
		if ( wp_doing_ajax() ) {
			return;
		}

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

		if ( ! ( tribe( Gateway::class )->is_enabled() && tribe( Merchant::class )->is_connected() ) ) {
			return;
		}

		tribe( Payment_Intent_Handler::class )->create_payment_intent_for_cart();
	}

	/**
	 * Handles the checkout request pieces related to Stripe Gateway.
	 *
	 * @since 5.19.3
	 *
	 * @return void
	 */
	public function handle_checkout_request() {
		$payment_intent_id            = tec_get_request_var( 'payment_intent' );
		$payment_intent_client_secret = tec_get_request_var( 'payment_intent_client_secret' );

		if ( ! ( $payment_intent_id && $payment_intent_client_secret ) ) {
			return;
		}

		$existing_payment_intent = tribe( Payment_Intent_Handler::class )->get();

		// Do we need to re-fecth the payment intent?
		if ( ! empty( $existing_payment_intent['id'] ) && ! empty( $existing_payment_intent['client_secret'] ) && $existing_payment_intent['id'] === $payment_intent_id && $existing_payment_intent['client_secret'] === $payment_intent_client_secret ) {
			$payment_intent = $existing_payment_intent;
		} else {
			$payment_intent = Payment_Intent::get( $payment_intent_id );
		}

		// Invalid payment intent, bail.
		if ( empty( $payment_intent['client_secret'] ) || $payment_intent['client_secret'] !== $payment_intent_client_secret ) {
			return;
		}

		// Overwrite the local payment intent, since we confirmed the one we received is the one in use.
		// This will be relevant for the checkout page.
		tribe( Payment_Intent_Handler::class )->set( $payment_intent );

		$success_url = add_query_arg( [ 'tc-order-id' => $payment_intent['id'] ], tribe( Success::class )->get_url() );
		$new_status  = tribe( Status::class )->convert_payment_intent_to_commerce_status( $payment_intent );

		$order = tec_tc_orders()->by_args(
			[
				'status'           => 'any',
				'gateway_order_id' => $payment_intent['id'],
			]
		)->first();

		if ( ! $order ) {
			return;
		}

		// We will attempt to update the order status to the one returned by Stripe.
		tribe( Order::class )->modify_status(
			$order->ID,
			$new_status->get_slug(),
			[
				'gateway_payload'  => $payment_intent,
				'gateway_order_id' => $payment_intent['id'],
			]
		);

		// If we get a success status, we redirect to the success page.
		if ( Completed::SLUG === $new_status->get_slug() ) {
			wp_safe_redirect( $success_url ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit, StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}
	}

	/**
	 * Modify the checkout variables to include errors and billing fields.
	 *
	 * @since 5.19.3
	 *
	 * @param array $vars The current template vars.
	 *
	 * @return array
	 */
	public function modify_checkout_vars( $vars ) {
		$payment_intent = tribe( Payment_Intent_Handler::class )->get();

		$vars['billing_fields']['name']['value']             = Arr::get( $payment_intent, [ 'metadata', 'purchaser_name' ], '' );
		$vars['billing_fields']['email']['value']            = Arr::get( $payment_intent, [ 'metadata', 'purchaser_email' ], '' );
		$vars['billing_fields']['address']['value']['line1'] = Arr::get( $payment_intent, [ 'shipping', 'address', 'line1' ], '' );
		$vars['billing_fields']['address']['value']['line2'] = Arr::get( $payment_intent, [ 'shipping', 'address', 'line2' ], '' );
		$vars['billing_fields']['city']['value']             = Arr::get( $payment_intent, [ 'shipping', 'address', 'city' ], '' );
		$vars['billing_fields']['state']['value']            = Arr::get( $payment_intent, [ 'shipping', 'address', 'state' ], '' );
		$vars['billing_fields']['zip']['value']              = Arr::get( $payment_intent, [ 'shipping', 'address', 'postal_code' ], '' );
		$vars['billing_fields']['country']['value']          = Arr::get( $payment_intent, [ 'shipping', 'address', 'country' ], '' );

		$redirect_status = tec_get_request_var( 'redirect_status' );
		if ( $redirect_status === 'failed' ) {
			$vars['has_error'] = true;
			$vars['error']     = [
				'title'   => esc_html__( 'Payment Failed', 'event-tickets' ),
				'message' => esc_html__( 'There was an issue processing your payment with your payment method. Please try again.', 'event-tickets' ),
			];
		}

		return $vars;
	}

	/**
	 * Modify the checkout whether to display billing fields.
	 *
	 * @since 5.19.3
	 *
	 * @param bool $value The current value.
	 *
	 * @return bool
	 */
	public function modify_checkout_display_billing_info( bool $value ): bool {
		$payment_methods       = tribe( Merchant::class )->get_payment_method_types();
		$count_payment_methods = count( $payment_methods );
		if ( 1 < $count_payment_methods ) {
			return true;
		}

		if ( 1 === $count_payment_methods && 'card' !== $payment_methods[0] ) {
			return true;
		}

		return $value;
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

	/**
	 * Filter Stripe currency precision based on Stripe's specific requirements.
	 *
	 * @since 5.26.7
	 *
	 * @param array  $currency_data The currency data from the map.
	 * @param string $currency_code The currency code.
	 * @param string $gateway The gateway name.
	 *
	 * @return array The modified currency data.
	 */
	public function filter_stripe_currency_precision( $currency_data, $currency_code, $gateway ) {
		return $this->container->make( Gateway::class )->filter_stripe_currency_precision( $currency_data, $currency_code, $gateway );
	}
}

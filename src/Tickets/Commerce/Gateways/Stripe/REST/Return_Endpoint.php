<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings;
use TEC\Tickets\Commerce\Gateways\Stripe\Signup;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use WP_REST_Server;
use WP_REST_Request;

/**
 * Class Return Endpoint.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Return_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected string $path = '/commerce/stripe/return';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_stripe_return' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Arguments used for the endpoint.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function create_order_args() {
		return [];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function handle_stripe_return( WP_REST_Request $request ) {
		$stripe_obj   = tribe_get_request_var( 'stripe' );
		$disconnected = tribe_get_request_var( 'stripe_disconnected' );

		if ( ! empty( $stripe_obj ) ) {
			$response = $this->decode_payload( $stripe_obj );

			if ( ! empty( $response->{'tc-stripe-error'} ) ) {
				$this->handle_connection_error( $response );
			}

			if ( ! empty( $response->stripe_disconnected ) && $response->stripe_disconnected ) {
				$this->handle_connection_terminated( [], $response );
			}

			$this->handle_connection_established( $response );
		}

		if ( ! empty( $disconnected ) ) {
			$this->handle_connection_terminated();
		}
	}

	/**
	 * Decode the payload received from WhoDat.
	 *
	 * @since 5.3.0
	 *
	 * @param string $payload json payload.
	 *
	 * @return object
	 */
	public function decode_payload( $payload ) {

		if ( empty( $payload ) ) {
			return;
		}

		return json_decode( base64_decode( $payload ) );
	}

	/**
	 * Handle successful account connections.
	 *
	 * @since 5.3.0
	 * @since 5.23.0 Updated redirect URL to the new settings page.
	 *
	 * @param object $payload data returned from WhoDat.
	 */
	public function handle_connection_established( $payload ) {
		$payload = (array) $payload;
		$webhook = false;

		if ( isset( $payload['webhook'] ) ) {
			$webhook = (array) $payload['webhook'];
			unset( $payload['webhook'] );
		}

		tribe( Merchant::class )->save_signup_data( $payload );
		tribe( Settings::class )->setup_account_defaults();

		$validate = tribe( Merchant::class )->validate_account_is_permitted();

		if ( 'valid' !== $validate ) {
			tribe( Merchant::class )->set_merchant_unauthorized( $validate );
			$disconnect_url = tribe( Signup::class )->generate_disconnect_url();

			tribe( Merchant::class )->delete_signup_data();
			wp_redirect( $disconnect_url );
			tribe_exit();
		}

		tribe( Merchant::class )->unset_merchant_unauthorized();
		$url = tribe( Plugin_Settings::class )->get_url(
			[
				'tab'       => Gateway::get_key(),
				'page'      => Plugin_Settings::$settings_page_id,
				'tc-status' => 'stripe-signup-complete',
			]
		);

		if ( ! empty( $webhook['id'] ) ) {
			tribe( Webhooks::class )->add_webhook( $webhook );
		}

		// Check for unfinished onboarding wizard.
		$wizard_data = get_option( 'tec_tickets_onboarding_wizard_data', [] );
		if ( ! empty( $wizard_data ) && ( ! isset( $wizard_data['finished'] ) || ! $wizard_data['finished'] ) ) {
			$url = admin_url( 'admin.php?page=tickets-setup' );
		}

		wp_safe_redirect( $url );
		tribe_exit();
	}

	/**
	 * Handle unsuccessful account connections.
	 *
	 * @since 5.3.0
	 * @since 5.23.0 Updated redirect URL to the new settings page.
	 *
	 * @param object $payload data returned from WhoDat.
	 */
	public function handle_connection_error( $payload ) {
		$url = tribe( Plugin_Settings::class )->get_url(
			[
				'tab'             => Gateway::get_key(),
				'page'            => Plugin_Settings::$settings_page_id,
				'tc-stripe-error' => $payload->{'tc-stripe-error'},
			]
		);

		wp_safe_redirect( $url );
		tribe_exit();
	}

	/**
	 * Handle account disconnections.
	 *
	 * @since 5.11.0
	 * @since 5.23.0 Updated redirect URL to the new settings page.
	 *
	 * @param array     $reason Reason of disconnect.
	 * @param ?stdClass $payload Data returned from WhoDat.
	 *
	 * @return void
	 */
	public function handle_connection_terminated( $reason = [], $payload = null ) {
		tribe( Merchant::class )->delete_signup_data();
		Gateway::disable();

		$query_args = [
			'tab'                 => Gateway::get_key(),
			'page'                => Plugin_Settings::$settings_page_id,
			'stripe_disconnected' => 1,
		];

		if ( isset( $payload->webhook, $payload->webhook->id ) ) {
			// Invalidate webhook related options.
			tribe_remove_option( tribe( Webhooks::class )::$option_webhooks_signing_key );
			tribe_remove_option( tribe( Webhooks::class )::$option_is_valid_webhooks );
		}

		$url_args = array_merge( $query_args, $reason );

		$url = tribe( Plugin_Settings::class )->get_url( $url_args );

		wp_safe_redirect( $url );
		tribe_exit();
	}
}

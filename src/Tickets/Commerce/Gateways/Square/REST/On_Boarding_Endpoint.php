<?php
/**
 * Square On-Boarding Endpoint
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use TEC\Tickets\Settings as Tickets_Commerce_Settings;
use TEC\Tickets\Commerce\Payments_Tab;
use WP_REST_Request;
use WP_REST_Server;

use Tribe__Date_Utils as Dates;

/**
 * Class On_Boarding_Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class On_Boarding_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST namespace for this endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $namespace = 'tribe/tickets/v1';

	/**
	 * The REST endpoint path for this endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/commerce/square/on-boarding';

	/**
	 * Get the namespace for this endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Get the path for this endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_path(): string {
		return $this->path;
	}

	/**
	 * Checks if the current user has permissions to the endpoint.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool Whether the current user can access the endpoint or not.
	 */
	public function has_permission( WP_REST_Request $request ) {
		$state = $request->get_param( 'state' );

		if ( empty( $state ) ) {
			return false;
		}

		return wp_verify_nonce( $state, tribe( WhoDat::class )->get_state_nonce_action() );
	}

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register(): void {
		$namespace = $this->get_namespace();
		$path      = $this->get_path();

		register_rest_route(
			$namespace,
			$path,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => [ $this, 'has_permission' ],
				'args'                => [
					'code'         => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
						},
					],
					'state'        => [
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( empty( $value ) ) {
								return false;
							}

							return wp_verify_nonce( $value, tribe( WhoDat::class )->get_state_nonce_action() );
						},
					],
					'token_type'   => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return $value === 'bearer';
						},
					],
					'access_token' => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
						},
					],
					'expires_at'   => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							$date = Dates::build_date_object( $value );
							$now  = Dates::build_date_object( 'now' );

							return $date > $now;
						},
					],
				],
			]
		);
	}

	/**
	 * Handles the request that creates or finalizes the signup of a new merchant with Square.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return void Request is handled via redirect.
	 */
	public function handle_request( WP_REST_Request $request ) {
		$params = $request->get_params();

		// If there's an error in the request, bail out.
		if ( ! empty( $params['error'] ) ) {
			// Log the error.
			do_action(
				'tribe_log',
				'error',
				'Square signup error',
				[
					'source'      => 'tickets-commerce-square',
					'error'       => $params['error'],
					'description' => $params['error_description'] ?? 'No description provided',
				]
			);

			$error_status = 'tc-square-signup-error';

			// Handle specific error cases.
			if ( 'user_denied' === $params['error'] ) {
				$error_status = 'tc-square-user-denied';
			}

			// Redirect back to the settings page with an error.
			$url = add_query_arg(
				[
					'tc-status'  => $error_status,
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			tribe_exit();
		}

		// If the response doesn't have the code and state, bail out.
		if (
			empty( $params['merchant_id'] )
			|| empty( $params['access_token'] )
			|| empty( $params['refresh_token'] )
		) {
			do_action(
				'tribe_log',
				'error',
				'Square token error',
				[
					'source'          => 'tickets-commerce-square',
					'message'         => 'Missing required OAuth parameters',
					'params_received' => array_keys( $params ),
				]
			);

			$url = add_query_arg(
				[
					'tc-status'  => 'tc-square-token-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			tribe_exit();
		}

		// Save the account data from the OAuth response.
		$saved = tribe( Merchant::class )->save_signup_data( $params );

		if ( ! $saved ) {
			do_action(
				'tribe_log',
				'error',
				'Square token save error',
				[
					'source'      => 'tickets-commerce-square',
					'message'     => 'Failed to save Square merchant credentials',
					'merchant_id' => $params['merchant_id'] ?? 'unknown',
				]
			);

			$url = add_query_arg(
				[
					'tc-status'  => 'tc-square-token-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			tribe_exit();
		}

		// Fetch additional merchant details from Square API.
		$merchant      = tribe( Merchant::class );
		$merchant_data = $merchant->fetch_merchant_data( true );

		// Log the retrieval attempt.
		if ( $merchant_data ) {
			do_action(
				'tribe_log',
				'info',
				'Square Merchant Data Retrieved',
				[
					'source'      => 'tickets-commerce',
					'merchant_id' => $params['merchant_id'],
				]
			);
		} else {
			do_action(
				'tribe_log',
				'warning',
				'Failed to retrieve Square Merchant Data during onboarding',
				[
					'source'      => 'tickets-commerce',
					'merchant_id' => $params['merchant_id'],
				]
			);
		}

		// Register webhooks for this merchant.
		$this->register_webhooks();

		// Enable the gateway.
		tribe_update_option( Tickets_Commerce_Settings::$tickets_commerce_enabled, true );
		tribe_update_option( Gateway::get_enabled_option_key(), true );

		// Redirect to the settings page.
		$url = add_query_arg(
			[
				'tc-section' => Gateway::get_key(),
			],
			tribe( Payments_Tab::class )->get_url()
		);

		wp_safe_redirect( $url );
		tribe_exit();
	}

	/**
	 * Register webhooks for the newly connected merchant.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_webhooks() {
		// Register the webhook.
		$webhook_data = tribe( Webhooks::class )->register_webhook_endpoint();

		if ( is_wp_error( $webhook_data ) || ! empty( $webhook_data['id'] ) ) {
			do_action(
				'tribe_log',
				'info',
				'Square webhook registered successfully',
				[
					'source'     => 'tickets-commerce-square',
					'webhook_id' => $webhook_data['id'] ?? '',
				]
			);
		} else {
			do_action(
				'tribe_log',
				'error',
				'Failed to register Square webhook during onboarding',
				[
					'source' => 'tickets-commerce-square',
					'error'  => $webhook_data,
				]
			);
		}
	}

	/**
	 * Returns the URL for redirecting users after an oAuth flow.
	 *
	 * @since TBD
	 *
	 * @param string|null $hash The hash to append to the URL.
	 *
	 * @return string
	 */
	public function get_return_url( $hash = null ): string {
		return rest_url( $this->get_namespace() . $this->get_path() );
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * @since TBD
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation(): array {
		return [
			'get' => [
				'summary'     => esc_html__( 'Handle Square OAuth callback', 'event-tickets' ),
				'description' => esc_html__( 'Handle redirect from Square after OAuth authorization', 'event-tickets' ),
				'responses'   => [
					'200' => [
						'description' => esc_html__( 'Processes the OAuth callback and redirects appropriately', 'event-tickets' ),
					],
					'400' => [
						'description' => esc_html__( 'Error handling the OAuth callback', 'event-tickets' ),
					],
				],
			],
		];
	}
}

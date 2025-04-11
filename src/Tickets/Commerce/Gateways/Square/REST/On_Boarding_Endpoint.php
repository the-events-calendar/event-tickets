<?php

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use TEC\Tickets\Commerce\Payments_Tab;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class On_Boarding_Endpoint
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
	protected $namespace = 'tribe/tickets/v1';

	/**
	 * The REST endpoint path for this endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/square/on-boarding';

	/**
	 * Get the namespace for this endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Get the path for this endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Checks if the current user has permissions to the endpoint.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current user can access the endpoint or not.
	 */
	public function has_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
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
					'code'             => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
						},
					],
					'state'            => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
						},
					],
					'error'            => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
						},
					],
					'error_description' => [
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => static function ( $value ) {
							if ( ! is_string( $value ) ) {
								return false;
							}

							return true;
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
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_request( WP_REST_Request $request ) {
		$params = $request->get_params();

		// If there's an error in the request, bail out.
		if ( ! empty( $params['error'] ) ) {
			// Log the error.
			tribe( 'logger' )->log_error(
				sprintf(
					'Square signup error: %s - %s',
					$params['error'],
					$params['error_description'] ?? 'No description provided'
				),
				'tickets-commerce-square'
			);

			// Redirect back to the settings page with an error.
			$url = add_query_arg(
				[
					'tc-status' => 'tc-square-signup-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			exit;
		}

		// If the response doesn't have the code and state, bail out.
		if ( empty( $params['code'] ) || empty( $params['state'] ) ) {
			$url = add_query_arg(
				[
					'tc-status' => 'tc-square-token-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			exit;
		}

		// Get the signup response data from WhoDat.
		$signup_data = [
			'code'  => $params['code'],
			'state' => $params['state'],
		];

		// Request the tokens from Square via WhoDat.
		$response = tribe( WhoDat::class )->onboard_account( $signup_data );

		if ( empty( $response ) || isset( $response['error'] ) ) {
			$url = add_query_arg(
				[
					'tc-status' => 'tc-square-token-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			exit;
		}

		// Save the account data.
		$saved = tribe( Merchant::class )->save_signup_data( $response );

		if ( ! $saved ) {
			$url = add_query_arg(
				[
					'tc-status' => 'tc-square-token-error',
					'tc-section' => Gateway::get_key(),
				],
				tribe( Payments_Tab::class )->get_url()
			);

			wp_safe_redirect( $url );
			exit;
		}

		// Redirect to the settings page.
		$url = add_query_arg(
			[
				'tc-section' => Gateway::get_key(),
			],
			tribe( Payments_Tab::class )->get_url()
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Returns the URL for redirecting users after an oAuth flow.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url( $hash = null ) {
		return rest_url( $this->get_namespace() . $this->get_path() );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_documentation() {
		return [
			'get' => [
				'summary'    => __( 'Handle Square OAuth callback', 'event-tickets' ),
				'description' => __( 'Handle redirect from Square after OAuth authorization', 'event-tickets' ),
				'responses'  => [
					'200' => [
						'description' => __( 'Processes the OAuth callback and redirects appropriately', 'event-tickets' ),
					],
					'400' => [
						'description' => __( 'Error handling the OAuth callback', 'event-tickets' ),
					],
				],
			],
		];
	}
}

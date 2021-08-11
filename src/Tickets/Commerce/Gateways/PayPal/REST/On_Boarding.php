<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use Tribe__Tickets__REST__V1__Endpoints__Base;
use Tribe__REST__Endpoints__CREATE_Endpoint_Interface;
use Tribe__Documentation__Swagger__Provider_Interface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class On_Boarding
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class On_Boarding
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__CREATE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The first URL segment for the routes.
	 *
	 * @since TBD
	 */
	const TICKETS_COMMERCE_NAMESPACE = 'tickets-commerce';

	/**
	 * The base URL route.
	 *
	 * @since TBD
	 */
	const TICKETS_COMMERCE_ROUTE = '/paypal/on-boarding';

	/**
	 * Registers the labels REST API route.
	 *
	 * @since TBD
	 */
	public function register() {
		register_rest_route( self::TICKETS_COMMERCE_NAMESPACE, self::TICKETS_COMMERCE_ROUTE, [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'save_paypal_seller_data' ],
			'permission_callback' => function () {
				return true;
			},
			'args'                => [
				'wp_nonce' => [
					'description'       => 'The nonce validation',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The wp_nonce argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
				'merchantId' => [
					'description'       => 'The merchant ID',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The merchantId argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
				'merchantIdInPayPal' => [
					'description'       => 'The merchant ID in PayPal',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The merchantIdInPayPal argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
				'permissionsGranted' => [
					'description'       => 'The merchant ID in PayPal',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The permissionsGranted argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
				'consentStatus' => [
					'description'       => 'The merchant ID in PayPal',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The consentStatus argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
				'accountStatus' => [
					'description'       => 'The merchant ID in PayPal',
					'required'          => true,
					'type'              => 'string',
					'validate_callback' => function ( $value ) {
						if ( ! is_string( $value ) ) {
							return new WP_Error( 'rest_invalid_param', 'The accountStatus argument must be a string.', [ 'status' => 400 ] );
						}

						return $value;
					},
					'sanitize_callback' => [ $this, 'sanitize_callback' ],
				],
			],
		] );
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since TBD
	 *
	 * @param  mixed  $value  Value of the 'filter' argument.
	 *
	 * @return string|array
	 */
	public function sanitize_callback( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/tickets-commerce/paypal/on-boarding';

	/**
	 * Gets the Endpoint path for the on boarding process.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_endpoint_path() {
		return $this->path;
	}

	/**
	 * Get the REST API route URL.
	 *
	 * @since TBD
	 *
	 * @return string The REST API route URL.
	 */
	public function get_route_url() {
		$rest     = tribe( REST::class );

		return rest_url( '/' . $rest->namespace . $this->get_endpoint_path(), 'https' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @todo WIPd
	 *
	 * @param WP_REST_Request $request   The request object.
	 * @param bool            $return_id Whether the created post ID should be returned or the full response object.
	 *
	 * @return WP_Error|WP_REST_Response|int An array containing the data on success or a WP_Error instance on failure.
	 */
	public function create( WP_REST_Request $request, $return_id = false ) {
		$event   = $request->get_body();
		$headers = $request->get_headers();

		/**
		 * @todo @nefeline On Boarding redirect here.
		 */

		$data = [
			'success' => true,
		];

		return new WP_REST_Response( $data );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function CREATE_args() {
		// Webhooks do not send any arguments, only JSON content.
		return [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current user can post or not.
	 */
	public function can_create() {
		// Always open, no further user-based validation.
		return true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function get_documentation() {
		return [
			'post' => [
				'consumes'   => [
					'application/json',
				],
				'parameters' => [],
				'responses'  => [
					'200' => [
						'description' => __( 'Processes the Webhook as long as it includes valid Payment Event data', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type'       => 'object',
									'properties' => [
										'success' => [
											'description' => __( 'Whether the processing was successful', 'event-tickets' ),
											'type'        => 'boolean',
										],
									],
								],
							],
						],
					],
					'403' => [
						'description' => __( 'The webhook was invalid and was not processed', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
								],
							],
						],
					],
				],
			],
		];
	}
}
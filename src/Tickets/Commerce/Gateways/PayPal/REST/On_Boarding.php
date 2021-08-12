<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use TEC\Tickets\Commerce\Gateways\PayPal\REST;
use Tribe__Tickets__REST__V1__Endpoints__Base;
use Tribe__REST__Endpoints__CREATE_Endpoint_Interface;
use Tribe__Documentation__Swagger__Provider_Interface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;


/**
 * Class On_Boarding
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class On_Boarding
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__CREATE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

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
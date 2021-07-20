<?php

namespace Tribe\Tickets\REST\V1\Endpoints\Commerce;

use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\PaymentCaptureCompleted;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhooksRoute;
use Tribe__Documentation__Swagger__Provider_Interface;
use Tribe__REST__Endpoints__CREATE_Endpoint_Interface;
use Tribe__Tickets__REST__V1__Endpoints__Base;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Webhook.
 *
 * @since   5.1.6
 * @package Tribe\Tickets\REST\V1\Endpoints\PayPal_Commerce
 */
class PayPal_Webhook
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__CREATE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $path = '/paypal-commerce/webhook';

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.1.6
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

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.1.6
	 *
	 * @param WP_REST_Request $request   The request object.
	 * @param bool            $return_id Whether the created post ID should be returned or the full response object.
	 *
	 * @return WP_Error|WP_REST_Response|int An array containing the data on success or a WP_Error instance on failure.
	 */
	public function create( WP_REST_Request $request, $return_id = false ) {
		$event   = $request->get_body();
		$headers = $request->get_headers();

		/** @var WebhooksRoute $webhook */
		$webhook = tribe( WebhooksRoute::class );

		try {
			$processed = $webhook->handle( $event, $headers );
		} catch ( \Exception $exception ) {
			$processed = false;
		}

		if ( ! $processed ) {
			$error   = 'webhook-not-processed';
			$message = $this->messages->get_message( $error );

			return new WP_Error( $error, $message, [ 'status' => 403 ] );
		}

		$data = [
			'success' => true,
		];

		return new WP_REST_Response( $data );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
	 *
	 * @return bool Whether the current user can post or not.
	 */
	public function can_create() {
		// Always open, no further user-based validation.
		return true;
	}
}

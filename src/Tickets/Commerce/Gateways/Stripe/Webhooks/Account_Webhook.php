<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Contracts\Webhook_Event_Interface;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Webhook for Account operations
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Account_Webhook implements Webhook_Event_Interface {

	/**
	 * Handler for Stripe webhook events in the payment_intent family.
	 *
	 * @since TBD
	 *
	 * @param array            $event
	 * @param Status_Interface $new_status
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Process the webhook and just return a valid WP_REST_Response.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_default( WP_REST_Request $request, WP_REST_Response $response ): WP_REST_Response {
		return $response;
	}

	/**
	 * @todo  We need to figure out what happens when this is the case.
	 *
	 * These will be directly sent to the Rest API.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request  $request
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response
	 */
	public static function handle_account_deauthorized( WP_REST_Request $request, WP_REST_Response $response ): WP_REST_Response {
		tribe( Merchant::class )->delete_signup_data();



		return $response;
	}
}
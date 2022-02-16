<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Contracts\Webhook_Event_Interface;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Status\Status_Interface;

use WP_REST_Request;
use WP_REST_Response;

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
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public static function handle( array $event, Status_Interface $new_status, WP_REST_Request $request ): bool {
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
	public static function handle_account_updated( WP_REST_Request $request, WP_REST_Response $response ): WP_REST_Response {
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

		$params = $request->get_json_params();
		$account_id = $params['account'];
		$current_id = tribe( Merchant::class )->get_client_id();

		if ( $account_id !== $current_id ) {
			new \WP_Error( '400', __( 'Account deauthorized is not the same as account connected.', 'event-tickets' ) );
		}


		tribe( Merchant::class )->set_merchant_deauthorized( 'tc-stripe-account-disconnected' );
		tribe( Merchant::class )->delete_signup_data();

		return $response;
	}
}
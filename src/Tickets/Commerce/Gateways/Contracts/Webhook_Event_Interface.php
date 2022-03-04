<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce\Status\Status_Interface;

/**
 * Interface for Webhook Event handler classes.
 */
interface Webhook_Event_Interface {

	/**
	 * Generic handler for webhook events.
	 *
	 * @since 5.3.0
	 *
	 * @param array             $event
	 * @param Status_Interface  $new_status
	 * @param \WP_REST_Request  $request
	 * @param \WP_REST_Response $response
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request, \WP_REST_Response $response );
}
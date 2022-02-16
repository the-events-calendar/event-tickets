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
	 * @since TBD
	 *
	 * @param array            $event
	 * @param Status_Interface $new_status
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 */
	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request );
}
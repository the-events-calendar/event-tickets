<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Interface for Webhook Event handler classes.
 */
interface Webhook_Event_Interface {

	public static function handle( array $event, Status_Interface $new_status, \WP_REST_Request $request );
}
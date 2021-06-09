<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

interface EventListener {

	/**
	 * This processes the PayPal Commerce webhook event passed to it.
	 *
	 * @since TBD
	 *
	 * @param object $event The PayPal payment event object.
	 *
	 * @return bool Whether the event was processed successfully.
	 */
	public function processEvent( $event );
}

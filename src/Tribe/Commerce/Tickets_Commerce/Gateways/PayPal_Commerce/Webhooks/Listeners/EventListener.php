<?php

namespace TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners;

interface EventListener {

	/**
	 * This processes the PayPal Commerce webhook event passed to it.
	 *
	 * @since TBD
	 *
	 * @param object $event
	 *
	 * @return void
	 */
	public function processEvent( $event );
}

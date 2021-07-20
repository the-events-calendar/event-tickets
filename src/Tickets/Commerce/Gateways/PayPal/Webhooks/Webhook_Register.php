<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\Event_Listener;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\Payment_Capture_Completed;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\Payment_Capture_Denied;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\Payment_Capture_Refunded;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\Payment_Capture_Reversed;

class Webhook_Register {

	/**
	 * Array of the PayPal webhook event handlers. Add-ons can use the registerEventHandler method
	 * to add additional events/handlers.
	 *
	 * Structure: PayPalEventName => EventHandlerClass
	 *
	 * @since 5.1.6
	 *
	 * @var string[]
	 */
	private $eventHandlers = [
		'PAYMENT.CAPTURE.COMPLETED' => Payment_Capture_Completed::class,
		'PAYMENT.CAPTURE.DENIED'    => Payment_Capture_Denied::class,
		'PAYMENT.CAPTURE.REFUNDED'  => Payment_Capture_Refunded::class,
		'PAYMENT.CAPTURE.REVERSED'  => Payment_Capture_Reversed::class,
	];

	/**
	 * Use this to register additional events and handlers
	 *
	 * @since 5.1.6
	 *
	 * @param string $payPalEvent  PayPal event to listen for, i.e. CHECKOUT.ORDER.APPROVED
	 * @param string $eventHandler The FQCN of the event handler
	 *
	 * @return $this
	 */
	public function registerEventHandler( $payPalEvent, $eventHandler ) {
		if ( isset( $this->eventHandlers[ $payPalEvent ] ) ) {
			throw new InvalidArgumentException( 'Cannot register an already registered event' );
		}

		if ( ! is_subclass_of( $eventHandler, Event_Listener::class ) ) {
			throw new InvalidArgumentException( 'Listener must be a subclass of ' . Event_Listener::class );
		}

		$this->eventHandlers[ $payPalEvent ] = $eventHandler;

		return $this;
	}

	/**
	 * Registers multiple event handlers using an array where the key is the
	 *
	 * @since 5.1.6
	 *
	 * @param array $handlers = [ 'PAYPAL.EVENT' => EventHandler::class ]
	 */
	public function registerEventHandlers( array $handlers ) {
		foreach ( $handlers as $event => $handler ) {
			$this->registerEventHandler( $event, $handler );
		}
	}

	/**
	 * Returns Event Listener instance for given event
	 *
	 * @since 5.1.6
	 *
	 * @param string $event
	 *
	 * @return Event_Listener
	 */
	public function getEventHandler( $event ) {
		return tribe( $this->eventHandlers[ $event ] );
	}

	/**
	 * Checks whether the given event is registered
	 *
	 * @since 5.1.6
	 *
	 * @param string $event
	 *
	 * @return bool
	 */
	public function hasEventRegistered( $event ) {
		return isset( $this->eventHandlers[ $event ] );
	}

	/**
	 * Returns an array of the registered events
	 *
	 * @since 5.1.6
	 *
	 * @return string[]
	 */
	public function get_registered_events() {
		return array_keys( $this->eventHandlers );
	}
}

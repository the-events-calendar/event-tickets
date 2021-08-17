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
	private $event_handlers = [
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
	 * @param string $paypal_event  PayPal event to listen for, i.e. CHECKOUT.ORDER.APPROVED
	 * @param string $event_handler The FQCN of the event handler
	 *
	 * @return $this
	 */
	public function register_event_handler( $paypal_event, $event_handler ) {
		if ( isset( $this->event_handlers[ $paypal_event ] ) ) {
			throw new InvalidArgumentException( 'Cannot register an already registered event' );
		}

		if ( ! is_subclass_of( $event_handler, Event_Listener::class ) ) {
			throw new InvalidArgumentException( 'Listener must be a subclass of ' . Event_Listener::class );
		}

		$this->event_handlers[ $paypal_event ] = $event_handler;

		return $this;
	}

	/**
	 * Registers multiple event handlers using an array where the key is the
	 *
	 * @since 5.1.6
	 *
	 * @param array $handlers = [ 'PAYPAL.EVENT' => EventHandler::class ]
	 */
	public function register_event_handlers( array $handlers ) {
		foreach ( $handlers as $event => $handler ) {
			$this->register_event_handler( $event, $handler );
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
	public function get_event_handler( $event ) {
		return tribe( $this->event_handlers[ $event ] );
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
	public function has_event_registered( $event ) {
		return isset( $this->event_handlers[ $event ] );
	}

	/**
	 * Returns an array of the registered events
	 *
	 * @since 5.1.6
	 *
	 * @return string[]
	 */
	public function get_registered_events() {
		return array_keys( $this->event_handlers );
	}
}

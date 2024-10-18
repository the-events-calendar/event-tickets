<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Status as Commerce_Status;

/**
 * Class Webhook Events.
 *
 * @link    https://stripe.com/docs/api/webhook_endpoints/create#create_webhook_endpoint-enabled_events
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\Webhooks
 */
class Events {

	/**
	 * Webhook Event name for when an application is deauthorized from the dashboard.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 * @link  https://stripe.com/docs/api/events/types#event_types-account.application.deauthorized
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const ACCOUNT_APPLICATION_DEAUTHORIZED = 'account.application.deauthorized';

	/**
	 * Webhook Event name when the account was updated.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const ACCOUNT_UPDATED = 'account.updated';

	/**
	 * Webhook Event name for when a charge expires before being captured.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const CHARGE_EXPIRED = 'charge.expired';

	/**
	 * Webhook Event name for when a charge capture fails.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const CHARGE_FAILED = 'charge.failed';

	/**
	 * Webhook Event name for when a charge is refunded.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const CHARGE_REFUNDED = 'charge.refunded';

	/**
	 * Webhook Event name for when a charge is completed.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const CHARGE_SUCCEEDED = 'charge.succeeded';

	/**
	 * Webhook Event name for a payment intent that was canceled.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_CANCELED = 'payment_intent.canceled';

	/**
	 * Webhook Event name for a payment intent that was created.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_CREATED = 'payment_intent.created';

	/**
	 * Webhook Event name for a payment intent that failed to create a charge.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_PAYMENT_FAILED = 'payment_intent.payment_failed';

	/**
	 * Webhook Event name for a payment intent that is in process.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_PROCESSING = 'payment_intent.processing';

	/**
	 * Webhook Event name for a payment intent that requires user action to complete a charge.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_REQUIRES_ACTION = 'payment_intent.requires_action';

	/**
	 * Webhook Event name for a payment intent that has successfully completed.
	 *
	 * @link  https://stripe.com/docs/api/payment_intents/object
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public const PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';

	/**
	 * Returns the handle to be used for each webhook event.
	 *
	 * @since 5.3.0
	 * @since 5.14.0 Removed Charge events that can be replaced by Payment Intent events.
	 *
	 * @return array
	 */
	public static function get_event_handlers(): array {
		$handlers = [
			static::ACCOUNT_UPDATED                  => [ Account_Webhook::class, 'handle_account_updated' ],
			static::ACCOUNT_APPLICATION_DEAUTHORIZED => [ Account_Webhook::class, 'handle_account_deauthorized' ],
			static::CHARGE_EXPIRED                   => [ Charge_Webhook::class, 'handle' ],
			static::CHARGE_FAILED                    => [ Charge_Webhook::class, 'handle' ],
			static::CHARGE_REFUNDED                  => [ Charge_Webhook::class, 'handle' ],
			static::CHARGE_SUCCEEDED                 => [ Charge_Webhook::class, 'handle' ],
			static::PAYMENT_INTENT_PROCESSING        => [ Payment_Intent_Webhook::class, 'handle' ],
			static::PAYMENT_INTENT_REQUIRES_ACTION   => [ Payment_Intent_Webhook::class, 'handle' ],
			static::PAYMENT_INTENT_SUCCEEDED         => [ Payment_Intent_Webhook::class, 'handle' ],
			static::PAYMENT_INTENT_PAYMENT_FAILED    => [ Payment_Intent_Webhook::class, 'handle' ],
			static::PAYMENT_INTENT_CANCELED          => [ Payment_Intent_Webhook::class, 'handle' ],
		];

		/**
		 * Allows filtering of the Webhook map of events-to-handler-functions for each one of the types we listen for.
		 *
		 * @since 5.3.0
		 *
		 * @param array $events The default map of event handler functions.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_event_handlers', $handlers );
	}

	/**
	 * Returns a list of all valid webhook events.
	 * If it converts directly to a TC status it will be the status Class name, otherwise it will be callable.
	 *
	 * @since 5.3.0
	 * @since 5.14.0 Removed Charge events that can be replaced by Payment Intent events.
	 *
	 * @return callable[]|Commerce_Status\Status_Interface[]
	 */
	public static function get_event_transition_status(): array {
		$events = [
			static::CHARGE_EXPIRED                 => Commerce_Status\Not_Completed::class,
			static::CHARGE_FAILED                  => Commerce_Status\Denied::class,
			static::CHARGE_REFUNDED                => Commerce_Status\Refunded::class,
			static::CHARGE_SUCCEEDED               => Commerce_Status\Completed::class,
			static::PAYMENT_INTENT_CANCELED        => Commerce_Status\Denied::class,
			static::PAYMENT_INTENT_CREATED         => Commerce_Status\Created::class,
			static::PAYMENT_INTENT_PAYMENT_FAILED  => Commerce_Status\Denied::class,
			static::PAYMENT_INTENT_PROCESSING      => Commerce_Status\Pending::class,
			static::PAYMENT_INTENT_REQUIRES_ACTION => Commerce_Status\Action_Required::class,
			static::PAYMENT_INTENT_SUCCEEDED       => Commerce_Status\Completed::class,
		];

		/**
		 * Allows filtering of the Webhook map of events-to-statuses for each one of the types we listen for.
		 *
		 * @since 5.3.0
		 *
		 * @param array $events The default map of which event statuses.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_status', $events );
	}

	/**
	 * Return webhook label's "Nice name", it's only applicable if the webhook converts to a status.
	 *
	 * @since 5.3.0
	 *
	 * @param string $event_name A Stripe Event String.
	 *
	 * @return string The Webhook label, false on error.
	 */
	public function get_webhook_label( string $event_name ): string {
		$labels = [
			static::PAYMENT_INTENT_CANCELED        => __( 'Canceled payments', 'event-tickets' ),
			static::PAYMENT_INTENT_CREATED         => __( 'Created payments', 'event-tickets' ),
			static::PAYMENT_INTENT_PAYMENT_FAILED  => __( 'Failed payments', 'event-tickets' ),
			static::PAYMENT_INTENT_PROCESSING      => __( 'Pending payments', 'event-tickets' ),
			static::PAYMENT_INTENT_REQUIRES_ACTION => __( 'Action required payments', 'event-tickets' ),
			static::PAYMENT_INTENT_SUCCEEDED       => __( 'Successful payments', 'event-tickets' ),
		];

		/**
		 * Allows filtering of the Webhook map of events for each one of the types we listen for.
		 *
		 * @since 5.3.0
		 *
		 * @param array  $labels     The default map of which event types that translate to a given label string.
		 * @param string $event_name Which event name we are looking for.
		 */
		$labels = apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_events_labels_map', $labels, $event_name );

		if ( ! static::is_valid( $event_name ) ) {
			return '';
		}

		if ( isset( $labels[ $event_name ] ) ) {
			return $labels[ $event_name ];
		}

		return '';
	}

	/**
	 * Checks if a given Stripe webhook event name is valid.
	 *
	 * @since 5.3.0
	 *
	 * @param string $event_name A Stripe Event String.
	 *
	 * @return bool
	 */
	public static function is_valid( string $event_name ): bool {
		$events_map = static::get_event_handlers();

		return isset( $events_map[ $event_name ] );
	}

	/**
	 * Converts a valid Stripe webhook event name into a commerce status object.
	 *
	 * @since 5.3.0
	 *
	 * @param string $event_name A Stripe Event String.
	 *
	 * @return false|Commerce_Status\Status_Interface|null
	 */
	public function convert_to_commerce_status( string $event_name ) {
		if ( ! static::is_valid( $event_name ) ) {
			return false;
		}

		$events = static::get_event_transition_status();

		if ( ! isset( $events[ $event_name ] ) ) {
			return false;
		}

		return tribe( Commerce_Status\Status_Handler::class )->get_by_class( $events[ $event_name ] );
	}
}

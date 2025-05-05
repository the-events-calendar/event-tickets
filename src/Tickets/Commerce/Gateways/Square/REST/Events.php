<?php
/**
 * The Square webhook events.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

/**
 * The Square webhook events.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class Events {
	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_PAYMENT_UPDATED = 'payment.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_PAYMENT_CREATED = 'payment.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_REFUND_CREATED = 'refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_REFUND_UPDATED = 'refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_ORDER_CREATED = 'order.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_ORDER_UPDATED = 'order.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_CUSTOMER_DELETED = 'customer.deleted';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_INVENTORY_COUNT_UPDATED = 'inventory.count.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_CHECKOUT_CREATED = 'terminal.checkout.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_CHECKOUT_UPDATED = 'terminal.checkout.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_REFUND_CREATED = 'terminal.refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_REFUND_UPDATED = 'terminal.refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_ACTION_CREATED = 'terminal.action.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const WEBHOOK_EVENT_TERMINAL_ACTION_UPDATED = 'terminal.action.updated';

	/**
	 * Webhook event types supported.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected const WEBHOOK_EVENT_TYPES = [
		self::WEBHOOK_EVENT_PAYMENT_UPDATED,
		self::WEBHOOK_EVENT_PAYMENT_CREATED,
		self::WEBHOOK_EVENT_REFUND_CREATED,
		self::WEBHOOK_EVENT_REFUND_UPDATED,
		self::WEBHOOK_EVENT_ORDER_CREATED,
		self::WEBHOOK_EVENT_ORDER_UPDATED,
		self::WEBHOOK_EVENT_CUSTOMER_DELETED,
		self::WEBHOOK_EVENT_INVENTORY_COUNT_UPDATED,
		self::WEBHOOK_EVENT_TERMINAL_CHECKOUT_CREATED,
		self::WEBHOOK_EVENT_TERMINAL_CHECKOUT_UPDATED,
		self::WEBHOOK_EVENT_TERMINAL_REFUND_CREATED,
		self::WEBHOOK_EVENT_TERMINAL_REFUND_UPDATED,
		self::WEBHOOK_EVENT_TERMINAL_ACTION_CREATED,
		self::WEBHOOK_EVENT_TERMINAL_ACTION_UPDATED,
	];

	/**
	 * Get the webhook event types supported by the Square gateway.
	 *
	 * @since TBD
	 *
	 * @return string[] The webhook event types.
	 */
	public static function get_webhook_event_types(): array {
		/**
		 * Filters the webhook event types supported by the Square gateway.
		 *
		 * @since TBD
		 *
		 * @param string[] $event_types The webhook event types.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_webhook_event_types', self::WEBHOOK_EVENT_TYPES );
	}
}

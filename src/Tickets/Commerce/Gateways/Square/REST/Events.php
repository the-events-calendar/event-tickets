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
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const PAYMENT_UPDATED = 'payment.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
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
	const REFUND_CREATED = 'refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const REFUND_UPDATED = 'refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ORDER_CREATED = 'order.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ORDER_UPDATED = 'order.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const CUSTOMER_DELETED = 'customer.deleted';

	/**
	 * Square Webhook Event Types
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const INVENTORY_COUNT_UPDATED = 'inventory.count.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_CHECKOUT_CREATED = 'terminal.checkout.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_CHECKOUT_UPDATED = 'terminal.checkout.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_REFUND_CREATED = 'terminal.refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_REFUND_UPDATED = 'terminal.refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_ACTION_CREATED = 'terminal.action.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const TERMINAL_ACTION_UPDATED = 'terminal.action.updated';

	/**
	 * Webhook event types supported.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected const TYPES = [
		self::REFUND_CREATED,
		self::REFUND_UPDATED,
		self::ORDER_CREATED,
		self::ORDER_UPDATED,
		self::CUSTOMER_DELETED,
		self::INVENTORY_COUNT_UPDATED,
	];

	/**
	 * Get the webhook event types supported by the Square gateway.
	 *
	 * @since TBD
	 *
	 * @return string[] The webhook event types.
	 */
	public static function get_types(): array {
		/**
		 * Filters the webhook event types supported by the Square gateway.
		 *
		 * @since TBD
		 *
		 * @param string[] $event_types The webhook event types.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_webhook_event_types', self::TYPES );
	}
}

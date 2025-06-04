<?php
/**
 * The Square webhook events.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

/**
 * The Square webhook events.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class Events {
	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const PAYMENT_UPDATED = 'payment.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const PAYMENT_CREATED = 'payment.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const REFUND_CREATED = 'refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const REFUND_UPDATED = 'refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const ORDER_CREATED = 'order.created';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const ORDER_UPDATED = 'order.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const CUSTOMER_DELETED = 'customer.deleted';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const INVENTORY_COUNT_UPDATED = 'inventory.count.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_CHECKOUT_CREATED = 'terminal.checkout.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_CHECKOUT_UPDATED = 'terminal.checkout.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_REFUND_CREATED = 'terminal.refund.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_REFUND_UPDATED = 'terminal.refund.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_ACTION_CREATED = 'terminal.action.created';

	/**
	 * Square Webhook Event Types
	 *
	 * Not used currently.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const TERMINAL_ACTION_UPDATED = 'terminal.action.updated';

	/**
	 * Square Webhook Event Types
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const OAUTH_AUTHORIZATION_REVOKED = 'oauth.authorization.revoked';

	/**
	 * Webhook event types supported.
	 *
	 * @since 5.24.0
	 *
	 * @var string[]
	 */
	protected const TYPES = [
		self::PAYMENT_CREATED,
		self::PAYMENT_UPDATED,
		self::REFUND_CREATED,
		self::REFUND_UPDATED,
		self::ORDER_CREATED,
		self::ORDER_UPDATED,
		self::CUSTOMER_DELETED,
		self::INVENTORY_COUNT_UPDATED,
		self::OAUTH_AUTHORIZATION_REVOKED,
	];

	/**
	 * Get the webhook event types supported by the Square gateway.
	 *
	 * @since 5.24.0
	 *
	 * @return string[] The webhook event types.
	 */
	public static function get_types(): array {
		/**
		 * Filters the webhook event types supported by the Square gateway.
		 *
		 * @since 5.24.0
		 *
		 * @param string[] $event_types The webhook event types.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_webhook_event_types', self::TYPES );
	}
}

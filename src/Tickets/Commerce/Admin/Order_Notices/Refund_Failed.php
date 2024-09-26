<?php
/**
 * Refund_Failed object to output the order status message.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Refund_Failed
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Refund_Failed extends Abstract_Notice {
	/**
	 * @inheritDoc
	 */
	public static function get_type(): string {
		return 'error';
	}

	/**
	 * @inheritDoc
	 */
	public static function get_i18n_message(): string {
		/* translators: %1$s: order number. */
		return _x(
			'Order #%1$s could not be marked as refunded. Please try again. You can still refund directly in the payment gateway.',
			'The message after an order refund failed.',
			'event-tickets'
		);
	}
}

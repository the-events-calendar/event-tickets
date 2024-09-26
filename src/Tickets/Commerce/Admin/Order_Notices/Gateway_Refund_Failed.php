<?php
/**
 * Gateway_Refund_Failed object to output the order status message.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Gateway_Refund_Failed
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Gateway_Refund_Failed extends Abstract_Notice {
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
		/* translators: %1$s: order number. %2$s: payment gateway. */
		return _x(
			'Order #%1$s could not be refunded via %2$s. Please try again or contact us for help.',
			'The message after an order refund failed in the gateway.',
			'event-tickets'
		);
	}
}

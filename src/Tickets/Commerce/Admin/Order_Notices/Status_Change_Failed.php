<?php
/**
 * Status_Change_Failed object to output the order status message.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

/**
 * Class Status_Change_Failed
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Status_Change_Failed extends Abstract_Notice {
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
		/* translators: %1$s: order number. %2$s: the current status. %3$s: the status to apply. */
		return _x(
			'Order #%1$s could not be changed from %2$s to %3$s. Please try again or get help.',
			'The message after an order status failed to change.',
			'event-tickets'
		);
	}
}

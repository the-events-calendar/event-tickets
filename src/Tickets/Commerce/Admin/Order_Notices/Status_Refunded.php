<?php
/**
 * Status_Refunded object to output the order status message.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Status_Refunded
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Status_Refunded extends Abstract_Notice {
	/**
	 * @inheritDoc
	 */
	public static function get_type(): string {
		return 'success';
	}

	/**
	 * @inheritDoc
	 */
	public static function get_i18n_message(): string {
		/* translators: %1$s: order number. */
		return _x(
			'Order #%1$s has been marked as refunded. Remember to process the refund in your payment gateway.',
			'The message after an order status was changed to refunded but requires an update in the payment gateway.',
			'event-tickets'
		);
	}
}

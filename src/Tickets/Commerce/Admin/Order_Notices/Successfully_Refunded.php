<?php

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Successfully_Refunded
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Successfully_Refunded extends Abstract_Notice {
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
		/* translators: %1$s: order number. %2$s: the payment gateway that processed the refund. */
		return _x(
			'Order #%1$s has been successfully refunded via %2$s',
			'The message after an order status was changed to refunded and refunded by the payment gateway.',
			'event-tickets'
		);
	}
}

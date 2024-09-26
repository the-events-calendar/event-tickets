<?php

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Partially_Refunded
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Partially_Refunded extends Abstract_Notice {
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
		/* translators: %1$s: order number. %2$s: the currency code, e.g. USD. %3$s: the amount refunded. */
		return _x(
			'Order #%1$s has been partially refunded %2$s %3$s',
			'The message after an order status was changed to refunded and partially refunded by the payment gateway.',
			'event-tickets'
		);
	}
}

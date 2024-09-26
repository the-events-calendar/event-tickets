<?php

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Gateway_Partial_Refund_Failed
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Gateway_Partial_Refund_Failed extends Abstract_Notice {
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
		/* translators: %1$s: refund amount. %2$s: order number. %3$s: payment gateway. */
		return _x(
			'The partial refund of %1$s could not be processed for Order #%2$s via %3$s.',
			'The message after a partial order refund failed in the gateway.',
			'event-tickets'
		);
	}
}

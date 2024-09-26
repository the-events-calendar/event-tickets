<?php
/**
 * Status_Change_Success object to output the order status message.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use TEC\Tickets\Commerce\Admin\Order_Notices\Abstract_Notice;

/**
 * Class Status_Change_Success
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
class Status_Change_Success extends Abstract_Notice {
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
		/* translators: %1$s: order number. %2$s: status the order was changed from. %3$s: status the order was changed to. */
		return _x(
			'Order #%1$s has been successfully updated from %2$s to %3$s',
			'The message after an order status was changed successfully.',
			'event-tickets'
		);
	}
}

<?php

namespace TEC\Tickets\Commerce\Gateways\Manual;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;

/**
 * Class Manual Gateway.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\Manual
 */
class Gateway extends Abstract_Gateway {
	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	protected static $key = 'manual';

	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	public static function get_label() {
		return __( 'Manually Generated', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	public static function is_connected() {
		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	public static function is_active() {
		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	public static function should_show() {
		return false;
	}
}
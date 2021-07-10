<?php
/**
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * The gateway related functionality.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways
 *
 */
abstract class Abstract_Gateway implements Interface_Gateway {

	/**
	 * The Gateway key.
	 *
	 * @since 5.1.6
	 */
	protected static $key;

	/**
	 * @inheritDoc
	 */
	public static function get_key() {
		return static::$key;
	}

	/**
	 * @inheritDoc
	 */
	public function register_gateway( array $gateways ) {
		$gateways[ static::get_key() ] = [
			'label'  => static::get_label(),
			'class'  => static::class,
			'object' => $this,
		];

		return $gateways;
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function should_show() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		return [];
	}

}

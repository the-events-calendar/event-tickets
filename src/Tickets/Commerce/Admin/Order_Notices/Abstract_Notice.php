<?php
/**
 * Abstract_Notice to build admin notice objects from.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */

namespace TEC\Tickets\Commerce\Admin\Order_Notices;

use Tribe__Admin__Notices;

/**
 * Class Abstract_Notice
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin\Order_Notices
 */
abstract class Abstract_Notice {

	/**
	 * @since TBD
	 *
	 * @return string The type of admin notice. Typically a 'success' or 'error'.
	 */
	abstract public static function get_type(): string;

	/**
	 * @since TBD
	 *
	 * @return string The parameterized translated string.
	 */
	abstract public static function get_i18n_message(): string;

	/**
	 * Returns the rendered admin notice.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$params The message params.
	 *
	 * @return string The rendered message.
	 */
	public static function get_message( ...$params ): string {
		$message = sprintf( static::get_i18n_message(), ...$params );

		return '<p>' . esc_html( $message ) . '</p>';
	}

	/**
	 * Registers this message with the admin notice transient.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$params Params for the translated message.
	 */
	public static function register_message( ...$params ) {
		$type    = static::get_type();
		$slug    = 'tec-tickets-commerce-order-status-update-notice';
		$message = static::get_message( ...$params );

		Tribe__Admin__Notices::instance()->undismiss( $slug );

		tribe_transient_notice(
			$slug,
			$message,
			[
				'type'     => $type,
				'dismiss'  => true,
				'action'   => 'admin_notices',
				'priority' => 1,
			],
			1
		);
	}
}

<?php
/**
 * Tickets Commerce: Free Gateway.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Module;
use Tribe__Template as Template;

/**
 * Class Free Gateway.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */
class Gateway extends Abstract_Gateway {
	/**
	 * The Gateway key.
	 *
	 * @since 5.10.0
	 *
	 * @var string $key The Gateway key.
	 */
	protected static string $key = 'free';

	/**
	 * The Gateway label, we are hiding it for this gateway.
	 *
	 * @since 5.10.0
	 *
	 * @return string The Gateway label.
	 */
	public static function get_label(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public static function is_enabled(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function is_connected(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active(): bool {
		return tribe( Module::class )->is_checkout_page() && static::should_show();
	}

	/**
	 * Determine whether the gateway should be shown as an available gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether the gateway should be shown as an available gateway.
	 */
	public static function should_show(): bool {
		if ( is_admin() ) {
			return false;
		}

		$cart_total = tribe( Cart::class )->get_cart_total();

		return 0 == $cart_total;
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		return [];
	}

	/**
	 * Render the checkout template.
	 *
	 * @since 5.10.0
	 *
	 * @param Template $template The template object.
	 */
	public function render_checkout_template( Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "gateway/{$gateway_key}/container";

		$template_vars = [
			'must_login' => ! is_user_logged_in() && tribe( Module::class )->login_required(),
		];

		return $template->template( $template_path, $template_vars );
	}
}

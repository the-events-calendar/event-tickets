<?php
namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Module;

/**
 * Class Free Gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */
class Gateway extends Abstract_Gateway {
	/**
	 * The Gateway key.
	 *
	 * @since TBD
	 *
	 * @var string $key The Gateway key.
	 */
	protected static $key = 'free';
	
	/**
	 * @inheritDoc
	 */
	public static function get_label() {
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
	public static function is_connected() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		return tribe( Module::class )->is_checkout_page() && static::should_show();
	}

	/**
	 * @inheritDoc
	 */
	public static function should_show() {
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
	 * @inheritDoc
	 */
	public function render_checkout_template( \Tribe__Template $template ): string {
		$gateway_key   = static::get_key();
		$template_path = "gateway/{$gateway_key}/container";
		
		echo '<Button id="tec-tc-gateway-free-checkout-button">Confirm Purchase</Button>';
		
		return '';
	}
}

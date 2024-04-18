<?php
namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Module;

class Gateway extends Abstract_Gateway {
	protected static $key = 'free';

	protected string $order_controller_class = Order::class;

	public static function get_label() {
		return __( 'Free', 'event-tickets' );
	}
	
	public static function is_enabled(): bool {
		return true;
	}

	public static function is_connected() {
		return true;
	}

	public static function is_active() {
		if ( ! tribe( Module::class )->is_checkout_page() ) {
			return false;
		}
		
		$items = tribe( Cart::class )->get_items_in_cart( true );
		$total = array_reduce(
			$items,
			function ( $carry, $item ) {
				return $carry + $item['sub_total']->get_string();
			},
			0 
		);
		
		if ( 0 == $total ) {
			return true;
		}
		
		return false;
	}

	public static function should_show() {
		return true;
	}
	
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
	
	public function register_gateway( $gateways ) {
		if ( ! static::is_active() ) {
			return $gateways;
		}
		
		$gateways[ static::get_key() ] = $this;
		
		return $gateways;
		
		return [ static::get_key() => $this ];
	}
}

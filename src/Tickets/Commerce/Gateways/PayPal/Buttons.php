<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Tribe__Utils__Array as Arr;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Cart;

/**
 * Class Buttons
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Buttons {

	/**
	 * Stores the instance of the template engine that we will use for rendering the elements.
	 *
	 * @since 5.1.9
	 *
	 * @var \Tribe__Template
	 */
	protected $template;

	/**
	 * Gets the template instance used to setup the rendering of the page.
	 *
	 * @since 5.1.9
	 *
	 * @return \Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/views/v2/commerce/gateway/paypal' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template;
	}

	/**
	 * Get the checkout script tag for PayPal buttons.
	 *
	 * @since 5.1.10
	 *
	 * @return string
	 */
	public function get_checkout_script() {
		// Bail if PayPal is not configured and active.
		if ( ! tribe( Merchant::class )->is_active() ) {
			return;
		}

		$items = tribe( Cart::class )->get_items_in_cart( true );

		// Bail if there are no tickets in cart.
		if ( empty( $items ) ) {
			return;
		}

		$client            = tribe( Client::class );
		$client_token_data = $client->get_client_token();
		$must_login        = ! is_user_logged_in() && tribe( Module::class )->login_required();
		$template_vars     = [
			'url'                     => $client->get_js_sdk_url(),
			'attribution_id'          => Gateway::ATTRIBUTION_ID,
			'client_token'            => Arr::get( $client_token_data, 'client_token' ),
			'client_token_expires_in' => Arr::get( $client_token_data, 'expires_in' ) - 60,
			'must_login'              => $must_login,
		];

		$html = $this->get_template()->template( 'checkout-script', $template_vars, false );

		tribe_asset_enqueue( 'tec-tickets-commerce-gateway-paypal-checkout' );

		return $html;
	}


	/**
	 * Include the payment buttons from PayPal into the Checkout page.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 */
	public function include_payment_buttons( $file, $name, $template ) {
		$must_login = ! is_user_logged_in() && tribe( Module::class )->login_required();

		$template->template( 'gateway/paypal/buttons', [ 'must_login' => $must_login ] );
	}


	/**
	 * Include the advanced payment fields from PayPal into the Checkout page.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 */
	public function include_advanced_payments( $file, $name, $template ) {
		$items = tribe( Cart::class )->get_items_in_cart( true );

		// Bail if there are no tickets in cart.
		if ( empty( $items ) ) {
			return;
		}

		$must_login = ! is_user_logged_in() && tribe( Module::class )->login_required();
		$merchant   = tribe( Merchant::class );

		$template->template(
			'gateway/paypal/advanced-payments',
			[
				'supports_custom_payments' => $merchant->get_supports_custom_payments(),
				'active_custom_payments'   => $merchant->get_active_custom_payments(),
				'must_login'               => $must_login,
			]
		);
	}
}
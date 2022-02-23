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
	 * Returns the variables for gateway's checkout template.
	 *
	 * @since 5.3.0
	 *
	 * @return []
	 */
	public function get_checkout_template_vars() {
		$client        = tribe( Client::class );

		$template_vars = [
			'url'            => $client->get_js_sdk_url(),
			'attribution_id' => Gateway::ATTRIBUTION_ID,
		];

		$client_token_data       = $client->get_client_token();
		$client_token            = Arr::get( $client_token_data, 'client_token' );
		$client_token_expires_in = Arr::get( $client_token_data, 'expires_in' );
		if ( ! empty( $client_token ) && ! empty( $client_token_expires_in ) ) {
			$template_vars['client_token'] = $client_token;
			$template_vars['client_token_expires_in'] = $client_token_expires_in - 60;
		}

		$merchant = tribe( Merchant::class );
		$template_vars['supports_custom_payments'] = $merchant->get_supports_custom_payments();
		$template_vars['active_custom_payments']   = $merchant->get_active_custom_payments();

		return $template_vars;
	}
}
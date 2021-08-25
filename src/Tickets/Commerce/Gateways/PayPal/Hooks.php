<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Commerce\Gateways\PayPal\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.commerce.gateways.paypal.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Commerce\Gateways\PayPal\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.commerce.gateways.paypal.hooks' ), 'some_method' ] );
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Shortcodes\Shortcode_Abstract;

/**
 * Class Hooks.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_actions() {
		// REST API Endpoint registration.
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'tec_tickets_commerce_admin_process_action:paypal-disconnect', [ $this, 'handle_action_disconnect' ] );
		add_action( 'tec_tickets_commerce_admin_process_action:paypal-refresh-access-token', [ $this, 'handle_action_refresh_token' ] );
		add_action( 'tec_tickets_commerce_admin_process_action:paypal-refresh-user-info', [ $this, 'handle_action_refresh_user_info' ] );

		add_action( 'tribe_template_before_include:tickets/v2/commerce/checkout/header', [ $this, 'include_client_js_sdk_script' ], 15, 3 );
		add_action( 'tribe_template_after_include:tickets/v2/commerce/checkout/footer', [ $this, 'include_payment_buttons' ], 15, 3 );
	}

	/**1
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_success_shortcode_checkout_page_paypal_template_vars', [ $this, 'include_checkout_page_vars' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_success_shortcode_success_page_paypal_template_vars', [ $this, 'include_success_page_vars' ], 10, 2 );
	}

	/**
	 * Filters the shortcode template vars for the Checkout page template.
	 *
	 * @since TBD
	 *
	 * @param array              $template_vars
	 * @param Shortcode_Abstract $shortcode
	 *
	 * @return array
	 */
	public function include_checkout_page_vars( $template_vars, $shortcode ) {
		$template_vars['merchant'] = tribe( Merchant::class );

		return $template_vars;
	}

	/**
	 * Filters the shortcode template vars for the Checkout page template.
	 *
	 * @since TBD
	 *
	 * @param array              $template_vars
	 * @param Shortcode_Abstract $shortcode
	 *
	 * @return array
	 */
	public function include_success_page_vars( $template_vars, $shortcode ) {
		$template_vars['merchant'] = tribe( Merchant::class );

		return $template_vars;
	}

	/**
	 * Include the Client JS SDK script into checkout.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 */
	public function include_client_js_sdk_script( $file, $name, $template ) {
		echo tribe( Buttons::class )->get_checkout_script();
	}

	/**
	 * Include the Client JS SDK script into checkout.
	 *
	 * @since TBD
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     Name of file file
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 */
	public function include_payment_buttons( $file, $name, $template ) {
		$template->template( 'gateway/paypal/buttons' );
	}

	/**
	 * Handles the disconnecting of the merchant.
	 *
	 * @todo  Display some message when disconnecting.
	 * @since TBD
	 *
	 */
	public function handle_action_disconnect() {
		$this->container->make( Merchant::class )->disconnect();
	}

	/**
	 * Handles the refreshing of the token from PayPal for this merchant.
	 *
	 * @todo  Display some message when refreshing token.
	 * @since TBD
	 *
	 */
	public function handle_action_refresh_token() {
		$merchant   = $this->container->make( Merchant::class );
		$token_data = $this->container->make( Client::class )->get_access_token_from_client_credentials( $merchant->get_client_id(), $merchant->get_client_secret() );

		$saved = $merchant->save_access_token_data( $token_data );
	}

	/**
	 * Handles the refreshing of the user info from PayPal for this merchant.
	 *
	 * @todo  Display some message when refreshing user info.
	 * @since TBD
	 *
	 */
	public function handle_action_refresh_user_info() {
		$merchant  = $this->container->make( Merchant::class );
		$user_info = $this->container->make( Client::class )->get_user_info();

		$saved = $merchant->save_user_info( $user_info );
	}

	/**
	 * Register the Endpoints from Paypal.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( REST::class )->register_endpoints();
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @since 5.1.6
	 *
	 * @param array $gateways List of available gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}
}

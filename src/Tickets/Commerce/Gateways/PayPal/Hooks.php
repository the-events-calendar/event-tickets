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
		// Frontend: PayPal Checkout.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_approve_order', [ $this, 'approve_order' ] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_approve_order', [ $this, 'approve_order' ] );

		// REST API Endpoint registration.
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
		add_action( 'tec_tickets_commerce_admin_process_action:paypal-disconnect', [ $this, 'handle_action_disconnect' ] );

		add_action( 'tribe_template_before_include:tickets/commerce/checkout/page-header', [ $this, 'include_client_js_sdk_script' ], 15, 3 );
		add_action( 'tribe_template_after_include:tickets/commerce/checkout/page-footer', [ $this, 'include_payment_buttons' ], 15, 3 );
	}

	/**1
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
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
		echo '<script src="' . tribe( Client::class )->get_js_sdk_url() . '" data-partner-attribution-id="' . esc_attr( \TEC\Tickets\Commerce\Gateways\PayPal\Gateway::ATTRIBUTION_ID ) . '"></script>';
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
	 * @since TBD
	 *
	 * @todo Display some message when disconnecting.
	 */
	public function handle_action_disconnect() {
		$this->container->make( Merchant::class )->disconnect();
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

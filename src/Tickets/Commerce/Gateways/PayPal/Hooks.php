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
		// Settings page: Connect PayPal.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_user_on_boarded', [ $this, 'on_boarded_user_ajax_request_handler' ] );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_get_partner_url', [ $this, 'on_get_partner_url_ajax_request_handler' ] );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_disconnect_account', [ $this, 'remove_paypal_account' ] );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_onboarding_trouble_notice', [ $this, 'on_boarding_trouble_notice' ] );
		add_action( 'admin_init', [ $this, 'on_boarding_boot' ] );

		// Frontend: PayPal Checkout.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_create_order', [ $this, 'create_order' ] );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_approve_order', [ $this, 'approve_order' ] );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_approve_order', [ $this, 'approve_order' ] );

		// REST API Endpoint registration.
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since 5.1.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
	}

	public function on_boarded_user_ajax_request_handler() {
		$this->container->make( AjaxRequestHandler::class )->onBoardedUserAjaxRequestHandler();
	}

	public function on_get_partner_url_ajax_request_handler() {
		$this->container->make( AjaxRequestHandler::class )->onGetPartnerUrlAjaxRequestHandler();
	}

	public function remove_paypal_account() {
		$this->container->make( AjaxRequestHandler::class )->removePayPalAccount();
	}

	public function on_boarding_trouble_notice() {
		$this->container->make( AjaxRequestHandler::class )->onBoardingTroubleNotice();
	}

	public function on_boarding_boot() {
		$this->container->make( onBoardingRedirectHandler::class )->boot();
	}

	public function create_order() {
		$this->container->make( AjaxRequestHandler::class )->createOrder();
	}

	public function approve_order() {
		$this->container->make( AjaxRequestHandler::class )->approveOrder();
	}

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
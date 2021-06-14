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
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Class Hooks.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'init', [ $this, 'register_assets' ] );

		// Settings page: Connect PayPal.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_user_on_boarded', $this->container->callback( AjaxRequestHandler::class, 'onBoardedUserAjaxRequestHandler' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_get_partner_url', $this->container->callback( AjaxRequestHandler::class, 'onGetPartnerUrlAjaxRequestHandler' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_disconnect_account', $this->container->callback( AjaxRequestHandler::class, 'removePayPalAccount' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_onboarding_trouble_notice', $this->container->callback( AjaxRequestHandler::class, 'onBoardingTroubleNotice' ) );
		add_action( 'admin_init', $this->container->callback( onBoardingRedirectHandler::class, 'boot' ) );

		// Frontend: PayPal Checkout.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_create_order', $this->container->callback( AjaxRequestHandler::class, 'createOrder' ) );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_create_order', $this->container->callback( AjaxRequestHandler::class, 'createOrder' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_approve_order', $this->container->callback( AjaxRequestHandler::class, 'approveOrder' ) );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_approve_order', $this->container->callback( AjaxRequestHandler::class, 'approveOrder' ) );

		// REST API Endpoint registration.
		add_action( 'rest_api_init', $this->container->callback( REST::class, 'register_endpoints' ) );

	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );
	}

	/**
	 * Add this gateway to the list of available.
	 *
	 * @since TBD
	 *
	 * @param array $gateways List of available gateways.
	 *
	 * @return array
	 */
	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}
}
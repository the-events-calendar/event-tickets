<?php
/**
 *
 * @todo This file is not being used currently but we need to remove this before we launch Tickets Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\Legacy
 */

namespace TEC\Tickets\Commerce\Gateways\Legacy;

use tad_DI52_ServiceProvider;

/**
 * Service provider for the Tickets Commerce: PayPal Standard (Legacy) gateway.
 *
 * @since   5.1.6
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		/**
		 * Allow filtering whether the PayPal Legacy classes should be registered.
		 *
		 * @since 5.1.6
		 *
		 * @param bool $should_register Whether the PayPal Legacy classes should be registered.
		 */
		$should_register = apply_filters( 'tribe_tickets_commerce_gateways_paypal_legacy_should_register', true );

		// Check whether we should continue registering the gateway classes.
		if ( false === $should_register ) {
			return;
		}

		$this->container->singleton( Gateway::class );
		$this->container->singleton( Settings::class );

		// @todo Determine what is still needed for PayPal Commerce to function.

		tribe_singleton( 'tickets.commerce.paypal.gateway', 'Tribe__Tickets__Commerce__PayPal__Gateway', [ 'build_handler' ] );
		tribe_singleton( 'tickets.commerce.paypal.notices', 'Tribe__Tickets__Commerce__PayPal__Notices' );
		tribe_singleton( 'tickets.commerce.paypal.endpoints', 'Tribe__Tickets__Commerce__PayPal__Endpoints', [ 'hook' ] );
		tribe_singleton( 'tickets.commerce.paypal.endpoints.templates.success', 'Tribe__Tickets__Commerce__PayPal__Endpoints__Success_Template' );
		tribe_singleton( 'tickets.commerce.paypal.orders.tabbed-view', 'Tribe__Tickets__Commerce__Orders_Tabbed_View' );
		tribe_singleton( 'tickets.commerce.paypal.orders.report', 'Tribe__Tickets__Commerce__PayPal__Orders__Report' );
		tribe_singleton( 'tickets.commerce.paypal.orders.sales', 'Tribe__Tickets__Commerce__PayPal__Orders__Sales' );
		tribe_singleton( 'tickets.commerce.paypal.screen-options', 'Tribe__Tickets__Commerce__PayPal__Screen_Options', [ 'hook' ] );
		tribe_singleton( 'tickets.commerce.paypal.stati', 'Tribe__Tickets__Commerce__PayPal__Stati' );
		tribe_singleton( 'tickets.commerce.paypal.currency', 'Tribe__Tickets__Commerce__Currency', [ 'hook' ] );
		tribe_singleton( 'tickets.commerce.paypal.links', 'Tribe__Tickets__Commerce__PayPal__Links' );
		tribe_singleton( 'tickets.commerce.paypal.oversell.policies', 'Tribe__Tickets__Commerce__PayPal__Oversell__Policies' );
		tribe_singleton( 'tickets.commerce.paypal.oversell.request', 'Tribe__Tickets__Commerce__PayPal__Oversell__Request' );
		tribe_singleton( 'tickets.commerce.paypal.frontend.tickets-form', 'Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form' );
		tribe_register( 'tickets.commerce.paypal.cart', 'Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged' );

		tribe()->tag( [
			'tickets.commerce.paypal.shortcodes.tpp-success' => 'Tribe__Tickets__Commerce__PayPal__Shortcodes__Success',
		], 'tpp-shortcodes' );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 5.1.6
	 */
	protected function hooks() {
		add_filter( 'tec_tickets_commerce_gateways', [ $this, 'filter_add_gateway' ], 10, 2 );

		add_action( 'init', tribe_callback( 'tickets.commerce.paypal.orders.report', 'hook' ) );

		// @todo The add_shortcode stuff should be in an init action.
		/** @var \Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface $shortcode */
		foreach ( tribe()->tagged( 'tpp-shortcodes' ) as $shortcode ) {
			add_shortcode( $shortcode->tag(), [ $shortcode, 'render' ] );
		}

		tribe( 'tickets.commerce.paypal.gateway' );
		tribe( 'tickets.commerce.paypal.orders.report' );
		tribe( 'tickets.commerce.paypal.screen-options' );
		tribe( 'tickets.commerce.paypal.endpoints' );
		tribe( 'tickets.commerce.paypal.currency' );
	}

	public function filter_add_gateway( array $gateways = [] ) {
		return $this->container->make( Gateway::class )->register_gateway( $gateways );
	}
}

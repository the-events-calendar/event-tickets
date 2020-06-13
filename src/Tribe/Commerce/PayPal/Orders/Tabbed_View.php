<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View
 *
 * @since 4.7
 */
class Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View {

	/**
	 * Adds the WooCommerce orders tab slug to the tab slug map.
	 *
	 * @since 4.7
	 *
	 * @param array $tab_map
	 *
	 * @return array
	 */
	public function filter_tribe_tickets_orders_tabbed_view_tab_map( array $tab_map = [] ) {
		$tab_map[ Tribe__Tickets__Commerce__PayPal__Orders__Report::$orders_slug ] = Tribe__Tickets__Commerce__PayPal__Orders__Report::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the PayPal orders tab among those the tabbed view should render.
	 *
	 * @since 4.7
	 *
	 * @param Tribe__Tabbed_View $tabbed_view
	 * @param WP_Post            $post
	 */
	public function register_orders_tab( Tribe__Tabbed_View $tabbed_view, WP_Post $post ) {
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post->ID );
		$not_using_paypal = ! $provider instanceof Tribe__Tickets__Commerce__PayPal__Main;
		$has_no_paypal_tickets = $not_using_paypal ? true : count( $provider->get_tickets_ids( $post ) ) === 0;

		if ( $not_using_paypal && $has_no_paypal_tickets ) {
			return;
		}

		$orders_report     = new Tribe__Tickets__Commerce__PayPal__Orders__Tab( $tabbed_view );
		$orders_report_url = Tribe__Tickets__Commerce__PayPal__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$tabbed_view->register( $orders_report );
	}

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since 4.7
	 */
	public function register() {
		add_filter( 'tribe_tickets_orders_tabbed_view_tab_map', [ $this, 'filter_tribe_tickets_orders_tabbed_view_tab_map' ] );
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_orders_tab' ], 10, 2 );
	}
}

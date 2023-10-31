<?php

namespace TEC\Tickets\Commerce\Reports;

use TEC\Tickets\Commerce\Reports\Orders;
use TEC\Tickets\Commerce\Module;

/**
 * Class Tabbed_View
 *
 * @since TBD
 */
class Tabbed_View {

	/**
	 * @var string
	 */
	public $active_tab_slug;

	/**
	 * Adds the Tickets Commerce orders tab slug to the tab slug map.
	 *
	 * @since TBD
	 *
	 * @param array $tab_map
	 *
	 * @return array
	 */
	public function filter_tickets_orders_tabbed_view_tab_map( array $tab_map = [] ) {
		$tab_map[ Orders::$page_slug ] = Orders::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the Ticket Commerce orders tab among those the tabbed view should render.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tabbed_View $tabbed_view
	 * @param WP_Post            $post
	 */
	public function register_orders_tab( \Tribe__Tabbed_View $tabbed_view, \WP_Post $post ) {
		/** @var \Module $commerce */
		$commerce = tribe( Module::class );

		if (
			! tribe_tickets_is_provider_active( $commerce )
			|| empty( $commerce->post_has_tickets( $post ) )
		) {
			return;
		}

		$post_id = \Tribe__Utils__Array::get( $_GET, 'event_id', \Tribe__Utils__Array::get( $_GET, 'post_id', false ), false );

		if ( empty( $post_id ) || ! $post = get_post( $post_id ) ) {
			return;
		}

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$orders_report     = new Orders_Tab( $tabbed_view );
		$orders_report_url = Orders::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$tabbed_view->register( $orders_report );
	}

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since TBD
	 */
	public function register() {
		add_filter( 'tribe_tickets_orders_tabbed_view_tab_map', [ $this, 'filter_tickets_orders_tabbed_view_tab_map' ] );
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_orders_tab' ], 10, 2 );
	}

	/**
	 * Sets the currently active tab slug.
	 *
	 * @since TBD
	 *
	 * @param string $tab_slug
	 */
	public function set_active( $tab_slug ) {
		$this->active_tab_slug = $tab_slug;
	}
}

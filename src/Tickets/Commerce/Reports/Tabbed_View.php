<?php

namespace TEC\Tickets\Commerce\Reports;

use TEC\Tickets\Commerce\Module;

use Tribe__Tickets__Attendees;
use Tribe__Tabbed_View;
use WP_Post;

/**
 * Class Tabbed_View
 *
 * @since 5.6.8
 */
class Tabbed_View {

	/**
	 * @since 5.6.8
	 *
	 * @var string
	 */
	public string $active_tab_slug;

	/**
	 * @since 5.6.8
	 *
	 * @var array A map that binds requested pages to tabs.
	 */
	protected array $tab_map = [
		'tickets-attendees' => 'tribe-tickets-attendance-report',
	];

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since 5.6.8
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'tec_tickets_commerce_reports_tabbed_view_tab_map', [ $this, 'include_order_tab' ] );
		add_action( 'tec_tickets_commerce_reports_tabbed_view_before_register_tab', [ $this, 'register_tabs' ], 10, 2 );

		// Legacy compatibility with Attendees page which is not part of Tickets Commerce.
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_tabs' ], 10, 2 );
	}

	/**
	 * Adds the Tickets Commerce orders tab slug to the tab slug map.
	 *
	 * @since 5.6.8
	 *
	 * @param array $tab_map
	 *
	 * @return array<string,string>
	 */
	public function include_order_tab( array $tab_map = [] ): array {
		$tab_map[ Orders::$page_slug ] = Orders::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the Ticket Commerce orders tab among those the tabbed view should render.
	 *
	 * @since 5.6.8
	 *
	 * @param Tribe__Tabbed_View $tabbed_view
	 * @param WP_Post            $post
	 *
	 * @return void
	 */
	public function register_tabs( Tribe__Tabbed_View $tabbed_view, WP_Post $post ): void {
		$commerce = tribe( Module::class );

		if (
			! tribe_tickets_is_provider_active( $commerce )
			|| empty( $commerce->post_has_tickets( $post ) )
		) {
			return;
		}

		$post_id = tribe_get_request_var( 'event_id', tribe_get_request_var( 'post_id' ) );
		$post = get_post( $post_id );

		if ( empty( $post_id ) || ! $post ) {
			return;
		}

		add_filter( 'tribe_tickets_attendees_show_title', '__return_false' );

		$orders_report     = new Orders_Tab( $tabbed_view );
		$orders_report_url = Orders::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$tabbed_view->register( $orders_report );
	}

	/**
	 * Renders the tabbed view for the current report.
	 *
	 * @since 5.6.8
	 *
	 * @return void
	 */
	public function render(): void {
		$post_id = tribe_get_request_var( 'event_id', tribe_get_request_var( 'post_id' ) );
		$post = get_post( $post_id );

		if ( empty( $post_id ) || ! $post ) {
			return;
		}

		$current_page = tribe_get_request_var( 'page' );

		$tabbed_view = new \Tribe__Tabbed_View();
		$tabbed_view->set_label( $this->get_title( $post_id ) );

		$request_uri = add_query_arg( [
			'post_id' => $post_id,
			'page' => $current_page,
			'post_type' => tribe_get_request_var( 'post_type' ),
		], admin_url( 'edit.php' ) );

		$tabbed_view->set_url( $request_uri );

		$tab_map = $this->get_tab_map();

		// try to set the active tab from the requested page
		if ( ! empty( $current_page ) && isset( $tab_map[ $current_page ] ) ) {
			$tabbed_view->set_active( $tab_map[ $current_page ] );
		}

		/**
		 * Fires before the tabbed view renders to allow for additional tabs registration before the default tabs are added.
		 *
		 * Note that the tabbed view will not render if only a tab is registered; tabs registered during this action will
		 * appear right (after) the default ones.
		 *
		 * @since 5.6.8
		 *
		 * @param Tribe__Tabbed_View $tabbed_view The tabbed view that is rendering.
		 * @param WP_Post            $post        The post orders should be shown for.
		 * @param string|null        $active      The currently active tab, use the `tec_tickets_commerce_reports_tabbed_view_tab_map` filter
		 *                                        to add tabs registered here to the map that will allow them to be activated.
		 */
		do_action( 'tec_tickets_commerce_reports_tabbed_view_before_register_tab', $tabbed_view, $post );

		/** @var Tribe__Tickets__Attendees $attendees */
		$attendees = tribe( 'tickets.attendees' );

		// Register the Attendees tab.
		$attendees_report = new Attendees_Tab( $tabbed_view );
		$attendees_report->set_url( $attendees->get_report_link( $post ) );
		$tabbed_view->register( $attendees_report );

		/**
		 * Fires before the tabbed view renders to allow for additional tabs registration after the default tabs are added.
		 *
		 * Note that the tabbed view will not render if only a tab is registered; tabs registered during this action will
		 * appear left (before) the default ones.
		 *
		 * @since 5.6.8
		 *
		 * @param Tribe__Tabbed_View $tabbed_view The tabbed view that is rendering.
		 * @param WP_Post            $post        The post orders should be shown for.
		 * @param string|null        $active      The currently active tab, use the `tec_tickets_commerce_reports_tabbed_view_tab_map` filter
		 *                                        to add tabs registered here to the map that will allow them to be activated.
		 */
		do_action( 'tec_tickets_commerce_reports_tabbed_view_after_register_tab', $tabbed_view, $post );

		// if there is only one tab registered then do not show the tabbed view
		if ( count( $tabbed_view->get() ) <= 1 ) {
			return;
		}

		if ( null !== $this->active_tab_slug ) {
			$tabbed_view->set_active( $this->active_tab_slug );
		}

		echo $tabbed_view->render();
	}

	/**
	 * Generates the title based on the page type and post ID.
	 *
	 * @since 5.6.8
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The generated title.
	 */
	public function get_title( int $post_id ): string {
		$page_type = tribe_get_request_var( 'page' );

		// Check $page_type to confirm if we are on Order or Attendees page.
		if ( $page_type === 'tickets-commerce-orders' ) {
			// Translators: %1$s: the post/event title, %2$d: the post/event ID.
			$title = _x( 'Orders for: %1$s [#%2$d]', 'orders report screen heading', 'event-tickets' );
		} else {
			// Translators: %1$s: the post/event title, %2$d: the post/event ID.
			$title = _x( 'Attendees for: %1$s [#%2$d]', 'attendees report screen heading', 'event-tickets' );
		}

		$view_title = sprintf( $title, get_the_title( $post_id ), $post_id );

		/**
		 * Filters the title on the Attendees, and Order list page.
		 *
		 * @since 5.6.8
		 *
		 * @param string 	$view_title The view title.
		 * @param int 		$post_id The post ID.
		 * @param string 	$page_type Possible values `tickets-attendees` or `tickets-orders`.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_reports_tabbed_page_title', $view_title, $post_id, $page_type );
	}

	/**
	 * Returns the attendee and orders tabbed view tabs to map the tab request slug to
	 * the registered tabs.
	 *
	 * @since 5.6.8
	 *
	 * @return array $tab_map An associative array in the [ <query_var> => <tab_slug> ] format.
	 */
	protected function get_tab_map(): array {
		/**
		 * Filters the attendee and orders tabbed view tabs to map the tab request slug to
		 * the registered tabs.
		 *
		 * The map will relate the GET query variable to the registered tab slugs.
		 *
		 * @since 5.6.8
		 *
		 * @param array $tab_map An associative array in the [ <query_var> => <tab_slug> ] format.
		 *
		 */
		return (array) apply_filters( 'tec_tickets_commerce_reports_tabbed_view_tab_map', $this->tab_map );
	}

	/**
	 * Sets the currently active tab slug.
	 *
	 * @since 5.6.8
	 *
	 * @param string $tab_slug
	 *
	 * @return void
	 */
	public function set_active( string $tab_slug ): void {
		$this->active_tab_slug = $tab_slug;
	}
}

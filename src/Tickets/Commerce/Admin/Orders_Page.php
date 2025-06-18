<?php
/**
 * Handles displaying of orders page and sub menu.
 *
 * @since 5.13.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */

namespace TEC\Tickets\Commerce\Admin;

use TEC\Tickets\Commerce\Order;
use Tribe\Admin\Pages;

/**
 * Class Orders_Page.
 *
 * @since 5.13.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Orders_Page {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Adds the Event Tickets Commerce Orders page.
	 *
	 * @since 5.13.0
	 */
	public function add_orders_page() {
		add_submenu_page(
			static::$parent_slug,
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->get_capability(),
			$this->get_menu_slug(),
			'',
			$this->get_position()
		);

		tec_asset(
			tribe( 'tickets.main' ),
			'event-tickets-commerce-admin-orders-css',
			'tickets-commerce/admin/orders/table.css',
			[],
			[ 'admin_enqueue_scripts' ],
			[ 'conditionals' => [ $this, 'is_admin_orders_page_or_admin_single_page' ] ]
		);

		// We only want to load this script if The Events Calendar is not active.
		tec_asset(
			tribe( 'tickets.main' ),
			'event-tickets-commerce-admin-orders',
			'admin/orders/table.js',
			[
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-datepicker',
				'tribe-attrchange',
			],
			[ 'admin_enqueue_scripts' ],
			[ 'conditionals' => [ $this, 'is_admin_orders_page_and_no_TEC' ] ]
		);

		tec_asset(
			tribe( 'tickets.main' ),
			'event-tickets-commerce-admin-orders-single-css',
			'tickets-commerce/admin/orders/single.css',
			[],
			[ 'admin_enqueue_scripts' ],
			[ 'conditionals' => [ $this, 'is_admin_single_page' ] ]
		);

		tec_asset(
			tribe( 'tickets.main' ),
			'event-tickets-commerce-admin-orders-single',
			'admin/orders/single.js',
			[
				'jquery',
				'tribe-datatables',
			],
			[ 'admin_enqueue_scripts' ],
			[ 'conditionals' => [ $this, 'is_admin_single_page' ] ]
		);
	}

	/**
	 * Get the title for the Orders page.
	 *
	 * @since 5.13.0
	 *
	 * @return string
	 */
	public function get_page_title() {
		/**
		 * Filters the title for the Orders page.
		 *
		 * @since 5.13.0
		 *
		 * @param string $page_title The title for the Orders page.
		 */
		return apply_filters( 'tribe_tickets_admin_order_page_page_title', esc_html__( 'Orders', 'event-tickets' ) );
	}

	/**
	 * Get the title for the Orders menu.
	 *
	 * @since 5.13.0
	 *
	 * @return string
	 */
	public function get_menu_title() {
		/**
		 * Filters the title for the Orders menu.
		 *
		 * @since 5.13.0
		 *
		 * @param string $menu_title The title for the Orders menu.
		 */
		return apply_filters( 'tribe_tickets_admin_order_page_menu_title', $this->get_page_title() );
	}

	/**
	 * Get the capability required to access the Orders page.
	 *
	 * @since 5.13.0
	 *
	 * @return string
	 */
	public function get_capability() {
		/**
		 * Filters the capability required to access the Orders page.
		 *
		 * @since 5.13.0
		 *
		 * @param string $capability The capability required to access the Orders page.
		 */
		return apply_filters( 'tribe_tickets_admin_order_page_capability', Pages::get_capability() );
	}

	/**
	 * Get the menu slug for the Orders page.
	 *
	 * @since 5.13.0
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		/**
		 * Filters the menu slug for the Orders page.
		 *
		 * @since 5.13.0
		 *
		 * @param string $menu_slug The menu slug for the Orders page.
		 */
		return apply_filters( 'tribe_tickets_admin_order_page_menu_slug', add_query_arg( 'post_type', Order::POSTTYPE, 'edit.php' ) );
	}

	/**
	 * Get the position of the Orders page.
	 *
	 * @since 5.13.0
	 *
	 * @return float
	 */
	public function get_position() {
		/**
		 * Filters the position of the Orders page.
		 *
		 * @since 5.13.0
		 *
		 * @param float $position The position of the Orders page.
		 */
		return apply_filters( 'tribe_tickets_admin_order_page_position', 1.7 );
	}

	/**
	 * Checks if the current screen is the orders page.
	 *
	 * @since 5.13.0
	 *
	 * @return bool
	 */
	public function is_admin_orders_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		if ( empty( $screen->id ) || 'edit-' . Order::POSTTYPE !== $screen->id ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the current screen is a single admin order page.
	 *
	 * @since 5.13.3
	 *
	 * @return bool
	 */
	public function is_admin_single_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		if ( empty( $screen->id ) || Order::POSTTYPE !== $screen->id ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the current screen is the admin orders page or a single admin order page.
	 *
	 * @since 5.13.3
	 *
	 * @return bool
	 */
	public function is_admin_orders_page_or_admin_single_page() {
		return $this->is_admin_orders_page() || $this->is_admin_single_page();
	}

	/**
	 * Checks if the current screen is the orders page and the Tickets Events Calendar is not active.
	 *
	 * @since 5.13.0
	 *
	 * @return bool
	 */
	public function is_admin_orders_page_and_no_TEC(): bool { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		if ( ! $this->is_admin_orders_page() ) {
			return false;
		}

		return ! defined( 'TRIBE_EVENTS_FILE' );
	}
}

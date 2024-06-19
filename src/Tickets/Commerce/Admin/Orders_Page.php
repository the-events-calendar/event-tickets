<?php
/**
 * Handles displaying of orders page and sub menu.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */

namespace TEC\Tickets\Commerce\Admin;

use TEC\Tickets\Commerce\Order;
use Tribe\Admin\Pages;

/**
 * Class Orders_Page.
 *
 * @since   TBD
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
	 * @since TBD
	 */
	public function add_orders_page() {
		add_submenu_page(
			static::$parent_slug,
			esc_html__( 'Orders', 'event-tickets' ),
			esc_html__( 'Orders', 'event-tickets' ),
			Pages::get_capability(),
			'edit.php?post_type=' . Order::POSTTYPE,
			'',
			1.7
		);

		tribe_asset(
			tribe( 'tickets.main' ),
			'event-tickets-commerce-admin-orders',
			'tickets-commerce/admin/orders/table.css',
			[],
			[ 'admin_enqueue_scripts' ],
			[ 'conditionals' => [ $this, 'is_admin_orders_page' ] ]
		);
	}

	/**
	 * Checks if the current screen is the orders page.
	 *
	 * @since TBD
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
}

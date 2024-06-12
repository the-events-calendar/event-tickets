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

/**
 * Manages the admin settings UI in relation to ticket configuration.
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
	}
}

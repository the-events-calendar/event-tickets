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
	 * Event Tickets Commerce Orders page slug.
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-commerce-orders';

	/**
	 * Adds the Event Tickets Commerce Orders page.
	 *
	 * @since TBD
	 */
	public function add_orders_page() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => 'edit.php?post_type=' . Order::POSTTYPE,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Orders', 'event-tickets' ),
				'position' => 1.7,
			]
		);
	}
}

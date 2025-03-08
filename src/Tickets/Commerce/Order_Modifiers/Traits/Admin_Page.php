<?php
/**
 * Admin Page trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

/**
 * Trait Admin_Page
 *
 * @since TBD
 */
trait Admin_Page {

	/**
	 * Event Tickets Order Modifiers page slug.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-order-modifiers';

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers page.
	 *
	 * @since 5.18.0
	 *
	 * @return bool True if on the Order Modifiers page, false otherwise.
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}
}

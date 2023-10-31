<?php

namespace TEC\Tickets\Commerce\Reports;

use TEC\Tickets\Commerce\Reports\Orders;

/**
 * Class Orders_Tab
 *
 * @since TBD
 */
class Orders_Tab extends \Tribe__Tabbed_View__Tab {
	/**
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * Returns this tab slug.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug() {
		return Orders::$tab_slug;
	}

	/**
	 * Returns this tab label
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Orders', 'event-tickets' );
	}
}

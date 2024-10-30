<?php

namespace TEC\Tickets\Commerce\Reports;

/**
 * Class Orders_Tab
 *
 * @since 5.6.8
 */
class Orders_Tab extends \Tribe__Tabbed_View__Tab {
	/**
	 * @inerhitDoc
	 *
	 * @since 5.6.8
	 */
	protected $visible = true;

	/**
	 * @inerhitDoc
	 *
	 * @since 5.6.8
	 */
	public function get_slug() {
		return Orders::$tab_slug;
	}

	/**
	 * @inerhitDoc
	 *
	 * @since 5.6.8
	 */
	public function get_label() {
		return esc_html__( 'Orders', 'event-tickets' );
	}
}

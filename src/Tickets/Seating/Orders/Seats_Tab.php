<?php

namespace TEC\Tickets\Seating\Orders;

use Tribe__Tabbed_View__Tab;
class Seats_Tab extends Tribe__Tabbed_View__Tab {
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	protected $visible = true;
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	public $priority = 90;
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	public function get_slug() {
		return Seats_Report::$tab_slug;
	}
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	public function get_label() {
		return esc_html__( 'Seats', 'event-tickets' );
	}
}
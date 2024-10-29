<?php
/**
 * Seats Tab class.
 */

namespace TEC\Tickets\Seating\Orders;

use Tribe__Tabbed_View__Tab;

/**
 * Class Seats_Tab
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Orders
 */
class Seats_Tab extends Tribe__Tabbed_View__Tab {
	/**
	 * @inerhitDoc
	 *
	 * @since 5.16.0
	 *
	 * @var bool
	 */
	protected $visible = true;

	/**
	 * @inerhitDoc
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	public $priority = 90;

	/**
	 * @inerhitDoc
	 *
	 * @since 5.16.0
	 */
	public function get_slug(): string {
		return Seats_Report::$tab_slug;
	}

	/**
	 * @inerhitDoc
	 *
	 * @since 5.16.0
	 */
	public function get_label(): string {
		return esc_html__( 'Seats', 'event-tickets' );
	}
}

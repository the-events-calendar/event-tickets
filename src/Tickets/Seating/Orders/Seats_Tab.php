<?php
/**
 * Seats Tab class.
 */
	
namespace TEC\Tickets\Seating\Orders;

use Tribe__Tabbed_View__Tab;

/**
 * Class Seats_Tab
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Orders
 */
class Seats_Tab extends Tribe__Tabbed_View__Tab {
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected $visible = true;
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public $priority = 90;
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	public function get_slug(): string {
		return Seats_Report::$tab_slug;
	}
	
	/**
	 * @inerhitDoc
	 *
	 * @since TBD
	 */
	public function get_label(): string {
		return esc_html__( 'Seats', 'event-tickets' );
	}
}

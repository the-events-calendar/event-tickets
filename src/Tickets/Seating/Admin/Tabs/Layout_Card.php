<?php
/**
 * The Layout card object class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

/**
 * The Layout_Card Class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */
class Layout_Card {
	/**
	 * The Layout ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $id;
		
	/**
	 * The Layout name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $name;
	
	/**
	 * The Layout map ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $map;
		
	/**
	 * The number of seats in the Layout.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $seats;
		
	/**
	 * The URL to the Layout's screenshot.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $screenshot_url;
		
	/**
	 * Layout_Card constructor.
	 *
	 * @since TBD
	 *
	 * @param string $id The Layout ID.
	 * @param string $name The Layout name.
	 * @param string $map The Layout map ID.
	 * @param int    $seats The number of seats in the Layout.
	 * @param string $screen_shot_url The URL to the Layout's screenshot.
	 */
	public function __construct( string $id, string $name, string $map, int $seats, string $screen_shot_url ) {
		$this->id             = $id;
		$this->name           = $name;
		$this->map            = $map;
		$this->seats          = $seats;
		$this->screenshot_url = $screen_shot_url;
	}
		
	/**
	 * Returns the Layout ID.
	 *
	 * @since TBD
	 *
	 * @return string The Layout ID.
	 */
	public function get_id(): string {
		return $this->id;
	}
		
	/**
	 * Returns the Layout name.
	 *
	 * @since TBD
	 *
	 * @return string The Layout name.
	 */
	public function get_name(): string {
		return $this->name;
	}
	
	/**
	 * Returns the Layout map ID.
	 *
	 * @since TBD
	 *
	 * @return string The Layout map.
	 */
	public function get_map(): string {
		return $this->map;
	}
		
	/**
	 * Returns the number of seats in the Layout.
	 *
	 * @since TBD
	 *
	 * @return int The number of seats in the Layout.
	 */
	public function get_seats(): int {
		return $this->seats;
	}
		
	/**
	 * Returns the URL to the Layout's screenshot.
	 *
	 * @since TBD
	 *
	 * @return string The URL to the Layout's screenshot.
	 */
	public function get_screenshot_url(): string {
		return $this->screenshot_url;
	}
}

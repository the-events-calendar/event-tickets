<?php
/**
 * The map card object class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

/**
 * The Map_Card Class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */
class Map_Card {
	/**
	 * The map ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * The map name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The number of seats in the map.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $seats;
	
	/**
	 * The URL to the map's screen shot.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $screenshot_url;

	/**
	 * Map_Card constructor.
	 *
	 * @since TBD
	 *
	 * @param string $id The map ID.
	 * @param string $name The map name.
	 * @param int    $seats The number of seats in the map.
	 * @param string $screen_shot_url The URL to the map's screen shot.
	 */
	public function __construct( string $id, string $name, int $seats, string $screen_shot_url ) {
		$this->id             = $id;
		$this->name           = $name;
		$this->seats          = $seats;
		$this->screenshot_url = $screen_shot_url;
	}

	/**
	 * Returns the map ID.
	 *
	 * @since TBD
	 *
	 * @return string The map ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Returns the map name.
	 *
	 * @since TBD
	 *
	 * @return string The map name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Returns the number of seats in the map.
	 *
	 * @since TBD
	 *
	 * @return int The number of seats in the map.
	 */
	public function get_seats(): int {
		return $this->seats;
	}
	
	/**
	 * Returns the URL to the map's screen shot.
	 *
	 * @since TBD
	 *
	 * @return string The URL to the map's screen shot.
	 */
	public function get_screenshot_url(): string {
		return $this->screenshot_url;
	}
}

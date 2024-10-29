<?php
/**
 * The map card object class.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;

/**
 * The Map_Card Class.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Admin\Tabs;
 */
class Map_Card {
	/**
	 * The map ID.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * The map name.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * The number of seats in the map.
	 *
	 * @since 5.16.0
	 *
	 * @var int
	 */
	protected int $seats;

	/**
	 * The URL to the map's screen shot.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected string $screenshot_url;

	/**
	 * Whether the map has layouts.
	 *
	 * @since 5.16.0
	 *
	 * @var bool
	 */
	protected bool $has_layouts;

	/**
	 * Map_Card constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string $id The map ID.
	 * @param string $name The map name.
	 * @param int    $seats The number of seats in the map.
	 * @param string $screen_shot_url The URL to the map's screen shot.
	 * @param bool   $has_layouts Whether the map has layouts.
	 */
	public function __construct( string $id, string $name, int $seats, string $screen_shot_url, bool $has_layouts = false ) {
		$this->id             = $id;
		$this->name           = $name;
		$this->seats          = $seats;
		$this->screenshot_url = $screen_shot_url;
		$this->has_layouts    = $has_layouts;
	}

	/**
	 * Returns the map ID.
	 *
	 * @since 5.16.0
	 *
	 * @return string The map ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Returns the map name.
	 *
	 * @since 5.16.0
	 *
	 * @return string The map name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Returns the number of seats in the map.
	 *
	 * @since 5.16.0
	 *
	 * @return int The number of seats in the map.
	 */
	public function get_seats(): int {
		return $this->seats;
	}

	/**
	 * Returns the URL to the map's screen shot.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to the map's screen shot.
	 */
	public function get_screenshot_url(): string {
		return $this->screenshot_url;
	}

	/**
	 * Returns the URL to edit the Layout.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to edit the Layout.
	 */
	public function get_edit_url(): string {
		return add_query_arg(
			[
				'page'  => Admin::get_menu_slug(),
				'tab'   => Map_Edit::get_id(),
				'mapId' => $this->get_id(),
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns the URL to create a new Layout for this Map.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to create a new Layout.
	 */
	public function get_create_layout_url(): string {
		return add_query_arg(
			[
				'action' => 'create',
				'page'   => Admin::get_menu_slug(),
				'tab'    => Layout_Edit::get_id(),
				'mapId'  => $this->get_id(),
				'isNew'  => '1',
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns whether the map has layouts.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the map has layouts.
	 */
	public function has_layouts(): bool {
		return $this->has_layouts;
	}
}

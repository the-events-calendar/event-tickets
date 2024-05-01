<?php
/**
 * The tab used to display the current site Maps.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;

/**
 * Class Maps.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin\Tabs;
 */
class Maps extends Tab {
	/**
	 * Returns the title of this tab. The one that will be displayed on the top of the page.
	 *
	 * @since TBD
	 *
	 * @return string The title of this tab.
	 */
	public function get_title(): string {
		return _x( 'Controller Maps', 'Tab title', 'events-assigned-seating' );
	}

	/**
	 * Returns the ID of this tab, used in the URL and CSS/JS attributes.
	 *
	 * @since TBD
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	public static function get_id(): string {
		return 'maps';
	}

	/**
	 * Renders the tab.
	 *
	 * @since TBD
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	public function render(): void {
		$context = [
			'cards'       => [],
			'add_new_url' => add_query_arg( [
				'page' => Admin::get_menu_slug(),
				'tab'  => Map_Edit::get_id()
			] ),
		];

		$this->template->template( 'tabs/maps', $context );
	}
}
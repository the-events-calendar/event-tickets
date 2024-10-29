<?php
/**
 * The tab used to display the current site Maps.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Admin\Tabs;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Template;
use TEC\Tickets\Seating\Service\Maps as Maps_Service;

/**
 * Class Maps.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Admin\Tabs;
 */
class Maps extends Tab {
	/**
	 * The Maps service.
	 *
	 * @since 5.16.0
	 *
	 * @var Maps_Service
	 */
	private Maps_Service $maps;

	/**
	 * The Maps Tab.
	 *
	 * @since 5.16.0
	 *
	 * @param Template     $template The template object.
	 * @param Maps_Service $maps The Maps service.
	 */
	public function __construct( Template $template, Maps_Service $maps ) {
		parent::__construct( $template );
		$this->maps = $maps;
	}
	/**
	 * Returns the title of this tab. The one that will be displayed on the top of the page.
	 *
	 * @since 5.16.0
	 *
	 * @return string The title of this tab.
	 */
	public function get_title(): string {
		return _x( 'Seating Maps', 'Tab title', 'event-tickets' );
	}

	/**
	 * Returns the ID of this tab, used in the URL and CSS/JS attributes.
	 *
	 * @since 5.16.0
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	public static function get_id(): string {
		return 'maps';
	}

	/**
	 * Renders the tab.
	 *
	 * @since 5.16.0
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	public function render(): void {
		$context = [
			'cards'       => $this->maps->get_in_card_format(),
			'add_new_url' => add_query_arg(
				[
					'page' => Admin::get_menu_slug(),
					'tab'  => Map_Edit::get_id(),
				]
			),
		];

		$this->template->template( 'tabs/maps', $context );
	}
}

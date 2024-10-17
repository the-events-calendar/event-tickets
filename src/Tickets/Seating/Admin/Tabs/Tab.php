<?php
/**
 * A tab used in the context of a page render
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */

namespace TEC\Tickets\Seating\Admin\Tabs;

use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Template;

/**
 * Class Tab.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */
abstract class Tab {
	/**
	 * The tab URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * A reference to the template object used to render this tab.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	protected Template $template;

	/**
	 * Tab constructor.
	 *
	 * @since TBD
	 *
	 * @param Template $template A reference to the template handle used to render this tab.
	 */
	public function __construct( Template $template ) {
		$this->url      = add_query_arg(
			[
				'page' => Admin::get_menu_slug(),
				'tab'  => static::get_id(),
			],
			admin_url( 'admin.php' )
		);
		$this->template = $template;
	}

	/**
	 * Returns the title of this tab. The one that will be displayed on the top of the page.
	 *
	 * @since TBD
	 *
	 * @return string The title of this tab.
	 */
	abstract public function get_title(): string;

	/**
	 * Returns the ID of this tab, used in the URL and CSS/JS attributes.
	 *
	 * @since TBD
	 *
	 * @return string The CSS/JS id of this tab.
	 */
	abstract public static function get_id(): string;

	/**
	 * Returns the URL of this tab.
	 *
	 * @since TBD
	 *
	 * @return string The URL of this tab.
	 */
	public function get_url(): string {
		return $this->url;
	}

	/**
	 * Renders the tab.
	 *
	 * @since TBD
	 *
	 * @return void The rendered HTML of this tab is passed to the output buffer.
	 */
	abstract public function render(): void;
}

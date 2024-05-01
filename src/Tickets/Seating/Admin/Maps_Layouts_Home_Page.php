<?php
/**
 * Renders and manages the state of the Maps and Layouts Home page.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Admin\Tabs\Tab;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\StellarWP\Assets\Assets;

/**
 * Class Maps_Layouts_Home_Page.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */
class Maps_Layouts_Home_Page {
	/**
	 * A reference to the template instance used to render the templates.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Maps_Layouts_Home_Page constructor.
	 *
	 * since TBD
	 *
	 * @param Template $template The template instance.
	 */
	public function __construct( Template $template ) {
		$this->template = $template;
	}

	/**
	 * Renders the maps home page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render(): void {
		$maps_id        = Maps::get_id();
		$layouts_id     = Layouts::get_id();
		$map_edit_id    = Map_Edit::get_id();
		$layout_edit_id = Layout_Edit::get_id();

		$tab = tribe_get_request_var( 'tab', $maps_id );

		if ( ! in_array( $tab,
			[
				$maps_id,
				$layouts_id,
				$map_edit_id,
				$layout_edit_id
			],
			true
		) ) {
			// If the tab is not valid, then we default to the Controller Configurations tab.
			$tab = $maps_id;
		}

		if ( $tab === $map_edit_id ) {
			$map_edit_tab = tribe( Map_Edit::class );
			$tabs         = [ $map_edit_tab ];
			$current      = $map_edit_tab;
		} elseif ( $tab === $layout_edit_id ) {
			$layout_edit_tab = tribe( Layout_Edit::class );
			$tabs            = [ $layout_edit_tab ];
			$current         = $layout_edit_tab;
		} else {
			$maps_tab    = tribe( Maps::class );
			$layouts_tab = tribe( Layouts::class );
			$tabs        = [ $maps_tab, $layouts_tab ];
			$current     = $tab === $maps_id ?
				$maps_tab
				: $layouts_tab;
		}

		/**
		 * Fires before the Maps and Layouts page renders.
		 *
		 * @since TBD
		 *
		 * @param Maps_Layouts_Home_Page $this    The Maps and Layouts page object.
		 * @param Tab                    $current The current tab.
		 * @param Tab[]                  $tabs    The set of tabs to render.
		 */
		do_action( "tec_events_assigned_seating_tab_{$tab}", $this, $current, $tabs );

		$this->template->template( 'maps-layouts-home', [
			'tabs'    => $tabs,
			'current' => $current,
		] );
	}
}
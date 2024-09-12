<?php
/**
 * Renders and manages the state of the Maps and Layouts Home page.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Tickets\Seating\Admin;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Admin\Tabs\Tab;
use TEC\Tickets\Seating\Service\Service;
use TEC\Tickets\Seating\Service\Service_Status;

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
	 * A reference to the service object.
	 *
	 * @since TBD
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Maps_Layouts_Home_Page constructor.
	 *
	 * @since TBD
	 *
	 * @param Template $template The template instance.
	 */
	public function __construct( Template $template, Service $service ) {
		$this->template = $template;
		$this->service  = $service;
	}

	/**
	 * Renders the maps home page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render(): void {
		$service_status = $this->service->get_status();

		if ( ! $service_status->is_ok() ) {
			$cta_url   = null;
			$cta_label = null;

			switch ( $service_status->get_status() ) {
				default:
				case Service_Status::SERVICE_DOWN:
					$message = __( 'The Seat Builder service is down. We are working to restore functionality.',
						'event-tickets' );
					break;
				case Service_Status::NOT_CONNECTED:
					$message   = __( 'You need to connect your site to the Seat Builder in order to create Seating Maps and Seat Layouts.',
						'event-tickets' );
					$cta_label = _x( 'Connect', 'Connect to the Seat Builder button label', 'event-tickets' );
					$cta_url = admin_url( 'admin.php?page=tec-tickets-settings&tab=licenses' );
					break;
				case Service_Status::INVALID_LICENSE:
					$message = __( 'Your license for Seating has expired. You need to renew your license to continue using Seating for Event Tickets.',
						'event-tickets' );
					break;
			}

			$this->template->template(
				'maps-layouts-home-error',
				[
					'message'   => $message,
					'cta_label' => $cta_label,
					'cta_url'   => $cta_url,
				]
			);

			return;
		}

		$maps_id        = Maps::get_id();
		$layouts_id     = Layouts::get_id();
		$map_edit_id    = Map_Edit::get_id();
		$layout_edit_id = Layout_Edit::get_id();

		$tab = tribe_get_request_var( 'tab', $maps_id );

		if ( ! in_array(
			$tab,
			[
				$maps_id,
				$layouts_id,
				$map_edit_id,
				$layout_edit_id,
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
		do_action( "tec_tickets_seating_tab_{$tab}", $this, $current, $tabs );

		$this->template->template(
			'maps-layouts-home',
			[
				'tabs'    => $tabs,
				'current' => $current,
			]
		);
	}

	/**
	 * Returns the URL of the Maps home page.
	 *
	 * @since TBD
	 *
	 * @return string The URL of the Maps home page.
	 */
	public function get_maps_home_url(): string {
		return add_query_arg(
			[
				'page' => Admin::get_menu_slug(),
				'tab'  => Maps::get_id(),
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Returns the URL of the Layouts home page.
	 *
	 * @since TBD
	 *
	 * @return string The URL of the Layouts home page.
	 */
	public function get_layouts_home_url(): string {
		return add_query_arg(
			[
				'page' => Admin::get_menu_slug(),
				'tab'  => Layouts::get_id(),
			],
			admin_url( 'admin.php' )
		);
	}
}

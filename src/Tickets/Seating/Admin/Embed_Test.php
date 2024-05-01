<?php
/**
 * The pseudo-tab used to test the embed functionality.
 *
 * @since TBD
 *
 * @package TEC\Controller\Admin;
 */

namespace TEC\Tickets\Seating\Admin;

use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Service\Service;

/**
 * Class Embed_Test.
 *
 * @since TBD
 *
 * @package TEC\Controller\Admin;
 */
class Embed_Test {
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
	 * since TBD
	 *
	 * @param Template $template The template instance.
	 */
	public function __construct( Template $template, Service $service ) {
		$this->template = $template;
		$this->service  = $service;
	}

	public static function get_menu_slug(): string {
		return 'tec-events-assigned-seating-embed-test';
	}

	public function render(): void {
		$route           = tribe_get_request_var( 'route' ) ?: '';
		$map_id          = tribe_get_request_var( 'mapId' );
		$layout_id       = tribe_get_request_var( 'layoutId' );
		$event_id        = tribe_get_request_var( 'eventId' );
		$ephemeral_token = $this->service->get_ephemeral_token();
		$token           = is_string( $ephemeral_token ) ? $ephemeral_token : '';
		$iframe_url      = add_query_arg(
			array_filter( [
				'token'    => $token,
				'mapId'    => $map_id,
				'layoutId' => $layout_id,
				'eventId'  => $event_id,
			] ),
			$this->service->get_frontend_url( '/embed/' . ltrim( $route, '/' ) )
		);

		$context = [
			'token'      => $token,
			'route'      => $route,
			'iframe_url' => $iframe_url,
		];

		// Piggyback on the Map_Edit tab scripts and styles.
		do_action( 'tec_events_assigned_seating_tab_' . Map_Edit::get_id() );

		$this->template->template( 'embed-test', $context );
	}
}

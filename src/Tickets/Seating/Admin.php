<?php
/**
 * The main Admin area controller.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Seating\Admin\Embed_Test;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Assets as Assets;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Admin.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Admin;
 */
class Admin extends Controller_Contract {
	use Built_Assets;

	/**
	 * A reference to the object representing the service.
	 *
	 * @since TBD
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Admin constructor.
	 *
	 * since TBD
	 *
	 * @param Container $container A reference to the container object.
	 * @param Service $service A reference to the service object.
	 */
	public function __construct( Container $container, Service $service ) {
		parent::__construct( $container );
		$this->service = $service;
	}

	/**
	 * Unhooks on the required hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$assets = Assets::instance();
		$assets->remove( 'tec-events-assigned-seating-admin-maps' );
		$assets->remove( 'tec-events-assigned-seating-admin-maps-style' );
		$assets->remove( 'tec-events-assigned-seating-admin-layouts' );
		$assets->remove( 'tec-events-assigned-seating-admin-layouts-style' );
		$assets->remove( 'tec-events-assigned-seating-admin-map-edit' );
		$assets->remove( 'tec-events-assigned-seating-admin-map-edit-style' );
		$assets->remove( 'tec-events-assigned-seating-admin-layout-edit' );
		$assets->remove( 'tec-events-assigned-seating-admin-layout-edit-style' );

		remove_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
		remove_action( 'admin_menu', [ $this, 'add_embed_submenu_page' ] );
	}

	/**
	 * Whether this Controller should be active or not.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the controller should be active or not.
	 */
	public function is_active(): bool {
		return is_admin();
	}

	/**
	 * Adds the seating management page under "Tickets" in the admin menu.
	 *
	 * @since TBD
	 *
	 * @return void The seating management page link is added to the admin menu.
	 */
	public function add_submenu_page(): void {
		add_submenu_page(
			'tec-tickets',
			__( 'Seat Layouts', 'events-assigned-seating' ),
			__( 'Seat Layouts', 'events-assigned-seating' ),
			'manage_options',
			self::get_menu_slug(),
			$this->container->callback( Admin\Maps_Layouts_Home_Page::class, 'render' )
		);
	}

	/**
	 * @todo remove this when embed testing is not required anymore.
	 */
	public function add_embed_submenu_page(): void {
		add_submenu_page(
			'tec-tickets',
			__( '__TEST__ Embed', 'events-assigned-seating' ),
			__( '__TEST__ Embed', 'events-assigned-seating' ),
			'manage_options',
			Embed_Test::get_menu_slug(),
			$this->container->callback( Admin\Embed_Test::class, 'render' )
		);
	}

	/**
	 * Returns the submenu page slug.
	 *
	 * @since TBD
	 *
	 * @return string The slug of the submenu page.
	 */
	public static function get_menu_slug(): string {
		return 'tec-events-assigned-seating';
	}

	/**
	 * Register the admin area bindings and hooks on the required hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Admin\Template::class );
		$this->container->singleton( Admin\Maps_Layouts_Home_Page::class );
		$this->container->singleton( Admin\Tabs\Maps::class );
		$this->container->singleton( Admin\Tabs\Map_Edit::class );
		$this->container->singleton( Admin\Tabs\Layouts::class );
		$this->container->singleton( Admin\Tabs\Layout_Edit::class );

		$this->register_built_asset(
			'tec-tickets-seating-utils',
			'utils.js'
		);
		$this->register_admin_bundle();
		$this->register_maps_assets();
		$this->reqister_layouts_assets();
		$this->register_map_edit_assets();
		$this->reqister_layout_edit_assets();

		add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );

		// TESTING STUFF
		add_action( 'admin_menu', [ $this, 'add_embed_submenu_page' ] );
	}

	/**
	 * Registers the main admin bundle, the common scripts and styles unsed by all admin bundles.
	 *
	 *
	 * @since TBD
	 *
	 * @return void The admin bundle script and styles are registered.
	 */
	private function register_admin_bundle(): void {
		$data = fn() => [
			'service'          => [
				'baseUrl' => $this->service->get_frontend_url(),
			],
			'localizedStrings' => [
				'service-errors' => $this->container->get( Localization::class )->get_service_error_strings(),
			]
		];

		$this->register_built_asset(
			'tec-tickets-seating-admin-bundle',
			'admin/bundle.js',
			[
				'wp-i18n',
				'tec-tickets-seating-vendor',
				'tec-tickets-seating-utils'
			],
			'admin_enqueue_scripts',
			[ 'tec.eventsAssignedSeating' => $data ]
		);
	}

	/**
	 * Registers the assets used by the Controller Maps tab.
	 *
	 * @since TBD
	 *
	 * @return void The assets are registered.
	 */
	private function register_maps_assets(): void {
		$action = 'tec_events_assigned_seating_tab_' . Maps::get_id();
		Asset::add(
			'tec-events-assigned-seating-admin-maps',
			'admin/maps.js',
			Plugin_Register::VERSION
		)
		     ->add_dependency( 'tec-events-assigned-seating-admin-bundle' )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->register();

		Asset::add(
			'tec-events-assigned-seating-admin-maps-style',
			'admin/maps.css',
			Plugin_Register::VERSION
		)
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->register();
	}

	/**
	 * Registers the assets used by the Seat Layouts tab.
	 *
	 * @since TBD
	 *
	 * @return void The assets are registered.
	 */
	private function reqister_layouts_assets(): void {
		$action = 'tec_events_assigned_seating_tab_' . Layouts::get_id();
		Asset::add(
			'tec-events-assigned-seating-admin-layouts',
			'admin/layouts.js',
			Plugin_Register::VERSION
		)
		     ->set_dependencies(
			     'tec-events-assigned-seating-admin-bundle',
			     'tribe-dialog-js'
		     )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->register();

		Asset::add(
			'tec-events-assigned-seating-admin-layouts-style',
			'admin/layouts.css',
			Plugin_Register::VERSION
		)
		     ->set_dependencies( 'tribe-dialog' )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->register();
	}

	/**
	 * Registers the assets used by the Controller Map edit page.
	 *
	 * @since TBD
	 *
	 * @return void The assets are registered.
	 */
	private function register_map_edit_assets(): void {
		$action = 'tec_events_assigned_seating_tab_' . Map_Edit::get_id();
		Asset::add(
			'tec-events-assigned-seating-admin-map-edit',
			'/admin/map-edit.js',
			Plugin_Register::VERSION
		)
		     ->add_dependency( 'tec-events-assigned-seating-admin-bundle' )
		     ->enqueue_on( $action )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->register();

		Asset::add(
			'tec-events-assigned-seating-admin-map-edit-style',
			'/admin/map-edit.css',
			Plugin_Register::VERSION
		)
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->register();
	}

	/**
	 * Registers the assets used by the Seat Layout edit page.
	 *
	 * @since TBD
	 *
	 * @return void The assets are registered.
	 */
	private function reqister_layout_edit_assets(): void {
		$action = 'tec_events_assigned_seating_tab_' . Layout_Edit::get_id();
		Asset::add(
			'tec-events-assigned-seating-admin-layout-edit',
			'admin/layout-edit.js',
			Plugin_Register::VERSION
		)
		     ->add_dependency( 'tec-events-assigned-seating-admin-bundle' )
		     ->enqueue_on( $action )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->register();

		Asset::add(
			'tec-events-assigned-seating-admin-layout-edit-style',
			'admin/layout-edit.css',
			Plugin_Register::VERSION
		)
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->add_to_group( 'tec-events-assigned-seating' )
		     ->enqueue_on( $action )
		     ->add_to_group( 'tec-events-assigned-seating-admin' )
		     ->register();
	}
}

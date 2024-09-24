<?php
/**
 * The main Admin area controller.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Seating\Admin\Embed_Test;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Tickets__Main as Tickets;
use Tribe\Tickets\Admin\Settings;

/**
 * Class Admin.
 *
 * @since TBD
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
	 * @since TBD
	 *
	 * @param Container $container A reference to the container object.
	 * @param Service   $service   A reference to the service object.
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
		$assets->remove( 'tec-tickets-seating-admin-maps' );
		$assets->remove( 'tec-tickets-seating-admin-maps-style' );
		$assets->remove( 'tec-tickets-seating-admin-layouts' );
		$assets->remove( 'tec-tickets-seating-admin-layouts-style' );
		$assets->remove( 'tec-tickets-seating-admin-map-edit' );
		$assets->remove( 'tec-tickets-seating-admin-map-edit-style' );
		$assets->remove( 'tec-tickets-seating-admin-layout-edit' );
		$assets->remove( 'tec-tickets-seating-admin-layout-edit-style' );

		remove_action( 'admin_menu', [ $this, 'add_submenu_page' ], 1000 );
		remove_action( 'admin_menu', [ $this, 'add_embed_submenu_page' ], 1000 );

		remove_action( 'admin_init', [ $this, 'register_woo_incompatibility_notice' ] );
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
			__( 'Seat Layouts', 'event-tickets' ),
			__( 'Seat Layouts', 'event-tickets' ),
			'manage_options',
			self::get_menu_slug(),
			$this->container->callback( Admin\Maps_Layouts_Home_Page::class, 'render' )
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
		return 'tec-tickets-seating';
	}

	/**
	 * @todo remove this when embed testing is not required anymore.
	 */
	public function add_embed_submenu_page(): void {
		add_submenu_page(
			'tec-tickets',
			__( '__TEST__ Embed', 'event-tickets' ),
			__( '__TEST__ Embed', 'event-tickets' ),
			'manage_options',
			Embed_Test::get_menu_slug(),
			$this->container->callback( Admin\Embed_Test::class, 'render' )
		);
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

		$this->register_maps_assets();
		$this->reqister_layouts_assets();
		$this->register_map_edit_assets();
		$this->reqister_layout_edit_assets();

		add_action( 'admin_menu', [ $this, 'add_submenu_page' ], 1000 );

		// @todo TEST STUFF remove when no more required.
		add_action( 'admin_menu', [ $this, 'add_embed_submenu_page' ], 1000 );

		add_action( 'admin_init', [ $this, 'register_woo_incompatibility_notice' ] );
	}

	/**
	 * Registers Seating incompatibility notice with WooCommerce.
	 *
	 * @since TBD
	 *
	 * @return void The notice is registered.
	 */
	public function register_woo_incompatibility_notice() {
		$message = sprintf(
			esc_html__( '%1$sTickets with assigned seating can only be created when selling with %3$sTickets Commerce%4$s. Support for WooCommerce sales will be included in a future release.%2$s' ),
			'<p>',
			'</p>',
			'<a href="' . esc_url( tribe( Settings::class )->get_url( [ 'tab' => 'payments' ] ) ) . '">',
			'</a>'
		);

		tribe_notice(
			'seating-incompatible-with-woo',
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			],
			static fn() => function_exists( 'WC' )
		);
	}

	/**
	 * Registers the assets used by the Seating Maps tab.
	 *
	 * @since TBD
	 *
	 * @return void The assets are registered.
	 */
	private function register_maps_assets(): void {
		$action = 'tec_tickets_seating_tab_' . Maps::get_id();
		Asset::add(
			'tec-tickets-seating-admin-maps',
			$this->built_asset_url( 'admin/maps.js' ),
			Tickets::VERSION
		)
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-maps-style',
			$this->built_asset_url( 'admin/maps.css' ),
			Tickets::VERSION
		)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
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
		$action = 'tec_tickets_seating_tab_' . Layouts::get_id();
		$data   = [
			'addLayoutModal' => 'dialog_obj_' . Layout_Edit::ADD_LAYOUT_MODAL_ID,
		];

		Asset::add(
			'tec-tickets-seating-admin-layouts',
			$this->built_asset_url( 'admin/layouts.js' ),
			Tickets::VERSION
		)
			->set_dependencies( 'tec-tickets-seating-service-bundle', 'tribe-dialog-js' )
			->add_localize_script( 'tec.tickets.seating.layouts', $data )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-layouts-style',
			$this->built_asset_url( 'admin/layouts.css' ),
			Tickets::VERSION
		)
			->set_dependencies( 'tribe-dialog' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
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
		$action = 'tec_tickets_seating_tab_' . Map_Edit::get_id();
		Asset::add(
			'tec-tickets-seating-admin-map-edit',
			$this->built_asset_url( 'admin/mapEdit.js' ),
			Tickets::VERSION
		)
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-map-edit-style',
			$this->built_asset_url( 'admin/mapEdit.css' ),
			Tickets::VERSION
		)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
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
		$action = 'tec_tickets_seating_tab_' . Layout_Edit::get_id();
		Asset::add(
			'tec-tickets-seating-admin-layout-edit',
			$this->built_asset_url( 'admin/layoutEdit.js' ),
			Tickets::VERSION
		)
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-layout-edit-style',
			$this->built_asset_url( 'admin/layoutEdit.css' ),
			Tickets::VERSION
		)
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->register();
	}
}

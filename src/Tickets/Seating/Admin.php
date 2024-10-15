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
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Tickets__Main as Tickets;
use Tribe\Tickets\Admin\Settings;
use Tribe__Admin__Helpers as Admin_Helper;

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
		remove_action( 'admin_init', [ $this, 'register_woo_incompatibility_notice' ] );
		remove_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [ $this, 'exclude_asc_events_from_candidates_from_moving_tickets_to' ] );
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
			__( 'Seating', 'event-tickets' ),
			__( 'Seating', 'event-tickets' ),
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
		add_action( 'admin_init', [ $this, 'register_woo_incompatibility_notice' ] );
		add_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [ $this, 'exclude_asc_events_from_candidates_from_moving_tickets_to' ] );
	}

	/**
	 * Excludes ASC events from the candidates to move tickets to.
	 *
	 * @since TBD
	 *
	 * @param array $query_args The query arguments.
	 *
	 * @return array The modified query arguments.
	 */
	public function exclude_asc_events_from_candidates_from_moving_tickets_to( array $query_args ): array {
		if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = []; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$query_args['meta_query'][] = [
			'relation' => 'OR',
			[
				'key'     => META::META_KEY_ENABLED,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => META::META_KEY_ENABLED,
				'value'   => '1',
				'compare' => '!=',
			],
		];

		if ( count( $query_args['meta_query'] ) > 1 ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		return $query_args;
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
			// Translators: %1$s and %2$s are opening/closing p tags, %3$s and %4$s are opening/closing a tags.
			esc_html__( '%1$sTickets with assigned seating can only be created when selling with %3$sTickets Commerce%4$s. Support for WooCommerce sales will be included in a future release.%2$s', 'event-tickets' ),
			'<p>',
			'</p>',
			'<a href="' . esc_url( tribe( Settings::class )->get_url( [ 'tab' => 'payments' ] ) ) . '">',
			'</a>'
		);

		$filter_callback = static fn() => [];
		$add_filter      = static fn() => add_filter( 'tribe_is_post_type_screen_post_types', $filter_callback );
		$remove_filter   = static fn() => remove_filter( 'tribe_is_post_type_screen_post_types', $filter_callback );

		$screen_ids = [
			'toplevel_page_tec-tickets',
			'tickets_page_tec-tickets-attendees',
			'edit-tec_tc_order',
			'tickets_page_tec-tickets-settings',
			'tickets_page_tec-tickets-help',
			'tickets_page_tec-tickets-troubleshooting',
			'edit-ticket-meta-fieldset',
			'tickets_page_tec-tickets-seating',
		];
		tribe_notice(
			'seating-incompatible-with-woo',
			$message,
			[
				'dismiss' => true,
				'type'    => 'warning',
			],
			static function () use ( $add_filter, $remove_filter, $screen_ids ) {
				$add_filter();
				$result = function_exists( 'WC' ) && Admin_Helper::instance()->is_screen( $screen_ids );
				$remove_filter();
				return $result;
			}
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

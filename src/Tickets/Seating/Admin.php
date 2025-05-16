<?php
/**
 * The main Admin area controller.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Layouts;
use TEC\Tickets\Seating\Admin\Tabs\Map_Edit;
use TEC\Tickets\Seating\Admin\Tabs\Maps;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Tickets__Main as Tickets;
use Tribe\Tickets\Admin\Settings;
use Tribe__Admin__Helpers as Admin_Helper;
use WP_Post;

/**
 * Class Admin.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Admin;
 */
class Admin extends Controller_Contract {
	/**
	 * A reference to the object representing the service.
	 *
	 * @since 5.16.0
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Admin constructor.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
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

		remove_action( 'admin_menu', [ $this, 'add_submenu_page' ], 15 );
		remove_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [ $this, 'exclude_asc_events_from_candidates_from_moving_tickets_to' ] );

		// Remove the hooks related to the seating meta data duplication.
		remove_filter( 'tec_events_pro_custom_tables_v1_duplicate_meta_data', [ $this, 'duplicate_seating_meta_data' ] );
		remove_action( 'tec_tickets_tickets_duplicated', [ $this, 'post_duplication_ticket_and_uuid_updates' ] );
	}

	/**
	 * Whether this Controller should be active or not.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the controller should be active or not.
	 */
	public function is_active(): bool {
		return is_admin();
	}

	/**
	 * Adds the seating management page under "Tickets" in the admin menu.
	 *
	 * @since 5.16.0
	 *
	 * @return void The seating management page link is added to the admin menu.
	 */
	public function add_submenu_page(): void {
		// Don't add the submenu page if there is no license for seating.
		if ( $this->service->get_status()->has_no_license() ) {
			return;
		}

		/** @var \Tribe\Admin\Pages */
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => self::get_menu_slug(),
				'parent'   => 'tec-tickets',
				'title'    => __( 'Seating', 'event-tickets' ),
				'path'     => self::get_menu_slug(),
				'position' => 4,
				'callback' => $this->container->callback( Admin\Maps_Layouts_Home_Page::class, 'render' ),
			]
		);
	}

	/**
	 * Returns the submenu page slug.
	 *
	 * @since 5.16.0
	 *
	 * @return string The slug of the submenu page.
	 */
	public static function get_menu_slug(): string {
		return 'tec-tickets-seating';
	}

	/**
	 * Register the admin area bindings and hooks on the required hooks.
	 *
	 * @since 5.16.0
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

		add_action( 'admin_menu', [ $this, 'add_submenu_page' ], 15 );
		add_filter( 'tec_tickets_find_ticket_type_host_posts_query_args', [ $this, 'exclude_asc_events_from_candidates_from_moving_tickets_to' ] );

		// Add the hooks related to the seating meta data duplication.
		add_filter( 'tec_events_pro_custom_tables_v1_duplicate_meta_data', [ $this, 'duplicate_seating_meta_data' ], 10, 2 );
		add_action( 'tec_tickets_tickets_duplicated', [ $this, 'post_duplication_ticket_and_uuid_updates' ], 10, 3 );
	}

	/**
	 * Updates the duplicated tickets and produces a new UUID for the duplicated post.
	 *
	 * @since 5.16.0
	 *
	 * @param array $duplicated_ticket_ids  The duplicated ticket IDs.
	 * @param int   $new_post_id            The new post ID.
	 * @param int   $old_post_id            The old post ID.
	 *
	 * @return void
	 */
	public function post_duplication_ticket_and_uuid_updates( array $duplicated_ticket_ids, int $new_post_id, int $old_post_id ) {
		if ( ! tec_tickets_seating_enabled( $old_post_id ) ) {
			return;
		}

		// Generate a new UUID for the duplicated seating post.
		update_post_meta( $new_post_id, Meta::META_KEY_UUID, wp_generate_uuid4() );

		// Copy the seating meta data of duplicated tickets.
		foreach ( $duplicated_ticket_ids as $original_ticket_id => $new_ticket_id ) {
			update_post_meta(
				$new_ticket_id,
				Meta::META_KEY_ENABLED,
				get_post_meta( $original_ticket_id, Meta::META_KEY_ENABLED, true )
			);

			update_post_meta(
				$new_ticket_id,
				Meta::META_KEY_SEAT_TYPE,
				get_post_meta( $original_ticket_id, Meta::META_KEY_SEAT_TYPE, true )
			);
		}
	}

	/**
	 * Duplicates the seating meta data.
	 *
	 * @since 5.16.0
	 *
	 * @param array   $meta   The meta data to duplicate.
	 * @param WP_Post $post The post object.
	 *
	 * @return array The duplicated meta data.
	 */
	public function duplicate_seating_meta_data( array $meta, WP_Post $post ): array {
		if ( ! tec_tickets_seating_enabled( $post->ID ) ) {
			return $meta;
		}

		return array_merge(
			$meta,
			[
				Meta::META_KEY_ENABLED,
				Meta::META_KEY_LAYOUT_ID,
			],
		);
	}

	/**
	 * Excludes ASC events from the candidates to move tickets to.
	 *
	 * @since 5.16.0
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
	 * Registers the assets used by the Seating Maps tab.
	 *
	 * @since 5.16.0
	 *
	 * @return void The assets are registered.
	 */
	private function register_maps_assets(): void {
		$action = 'tec_tickets_seating_tab_' . Maps::get_id();
		Asset::add(
			'tec-tickets-seating-admin-maps',
			'admin/maps.js',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-maps-style',
			'admin/style-maps.css',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();
	}

	/**
	 * Registers the assets used by the Seat Layouts tab.
	 *
	 * @since 5.16.0
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
			'admin/layouts.js',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_dependencies( 'tec-tickets-seating-service-bundle', 'tribe-dialog-js' )
			->add_localize_script( 'tec.tickets.seating.layouts', $data )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-layouts-style',
			'admin/style-layouts.css',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_dependencies( 'tribe-dialog' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->register();
	}

	/**
	 * Registers the assets used by the Controller Map edit page.
	 *
	 * @since 5.16.0
	 *
	 * @return void The assets are registered.
	 */
	private function register_map_edit_assets(): void {
		$action = 'tec_tickets_seating_tab_' . Map_Edit::get_id();
		Asset::add(
			'tec-tickets-seating-admin-map-edit',
			'admin/mapEdit.js',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-map-edit-style',
			'admin/style-mapEdit.css',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->register();
	}

	/**
	 * Registers the assets used by the Seat Layout edit page.
	 *
	 * @since 5.16.0
	 *
	 * @return void The assets are registered.
	 */
	private function reqister_layout_edit_assets(): void {
		$action = 'tec_tickets_seating_tab_' . Layout_Edit::get_id();
		Asset::add(
			'tec-tickets-seating-admin-layout-edit',
			'admin/layoutEdit.js',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_dependency( 'tec-tickets-seating-service-bundle' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-admin-layout-edit-style',
			'admin/style-layoutEdit.css',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->add_to_group( 'tec-tickets-seating-admin' )
			->add_to_group( 'tec-tickets-seating' )
			->enqueue_on( $action )
			->add_to_group( 'tec-tickets-seating-admin' )
			->register();
	}
}

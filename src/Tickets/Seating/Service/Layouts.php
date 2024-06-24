<?php
/**
 * The service component used to fetch the Layouts from the service.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;

/**
 * Class Layouts.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
class Layouts {
	/**
	 * The URL to the service used to fetch the layouts from the backend.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $service_fetch_url;

	/**
	 * Layouts constructor.
	 *
	 * @since TBD
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 */
	public function __construct( string $backend_base_url ) {
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/layouts';
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since TBD
	 *
	 * @param array<array{
	 *     id?: string,
	 *     name?: string,
	 *     seats?: int,
	 *     mapId?: string,
	 *     createdDate?: string,
	 * }> $service_rows The rows to insert.
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function insert_rows_from_service( array $service_rows ) {
		$valid = array_reduce(
			$service_rows,
			static function ( array $valid, array $service_row ): array {
				if ( ! isset(
					$service_row['id'],
					$service_row['name'],
					$service_row['seats'],
					$service_row['mapId'],
					$service_row['createdDate'],
					$service_row['screenshotUrl']
				) ) {
					return $valid;
				}

				$created_date_in_ms = $service_row['createdDate'];
				$created_date       = gmdate( 'Y-m-d H:i:s', $created_date_in_ms / 1000 );

				$valid[] = [
					'id'             => $service_row['id'],
					'name'           => $service_row['name'],
					'seats'          => $service_row['seats'],
					'map'            => $service_row['mapId'],
					'screenshot_url' => $service_row['screenshotUrl'],
					'created_date'   => $created_date,
				];

				return $valid;
			},
			[]
		);

		if ( ! count( $valid ) ) {
			return 0;
		}

		return Layouts_Table::insert_many( $valid );
	}

	/**
	 * Returns the layouts in option format.
	 *
	 * @since TBD
	 *
	 * @return array<string, array{id: string, name: string, seats: int}> The layouts in option format.
	 */
	public function get_in_option_format() {
		if ( ! $this->update() ) {
			return [];
		}

		$layouts = wp_cache_get( 'option_format_layouts', 'tec-tickets-seating' );

		if ( ! ( $layouts && is_array( $layouts ) ) ) {
			$layouts = [];
			foreach ( Layouts_Table::fetch_all() as $row ) {
				$layouts[] = [
					'id'    => $row->id,
					'name'  => $row->name,
					'seats' => $row->seats,
				];
			}

			wp_cache_set(
				'option_format_layouts',
				$layouts,
				'tec-tickets-seating',
				self::update_transient_expiration() // phpcs:ignore
			);
		}

		return $layouts;
	}

	/**
	 * Fetches all the Layouts from the database.
	 *
	 * @since TBD
	 *
	 * @return Layout_Card[] Array of layout card objects.
	 */
	public function get_in_card_format() {
		if ( ! $this->update() ) {
			return [];
		}

		$mem_key      = 'option_layout_card_objects';
		$cache        = tribe_cache();
		$layout_cards = $cache[ $mem_key ];

		if ( ! ( $layout_cards && is_array( $layout_cards ) ) ) {
			$layout_cards = [];
			foreach ( Layouts_Table::fetch_all() as $row ) {
				$layout_cards[] = new Layout_Card(
					$row->id,
					$row->name,
					$row->map,
					$row->seats,
					$row->screenshot_url
				);
			}

			$cache[ $mem_key ] = $layout_cards;
		}

		return $layout_cards;
	}

	/**
	 * Updates the layouts from the service by updating the caches and custom tables.
	 *
	 * @since TBD
	 *
	 * @param bool $force If true, the layouts will be updated even if they are up-to-date.
	 *
	 * @return bool Whether the layouts are up-to-date or not.
	 */
	public function update( bool $force = false ) {
		$updater = new Updater( $this->service_fetch_url, self::update_transient_name(), self::update_transient_expiration() );

		return $updater->check_last_update( $force )
		               ->update_from_service( [ $this, 'invalidate_cache' ] )
		               ->store_fetched_data( [ $this, 'insert_rows_from_service' ] );
	}

	/**
	 * Returns the transient name used to store the last update time.
	 *
	 * @since TBD
	 *
	 * @return string The transient name used to store the last update time.
	 */
	public static function update_transient_name(): string {
		return 'tec_tickets_seating_layouts_last_update';
	}

	/**
	 * Returns the expiration time in seconds.
	 *
	 * @since TBD
	 *
	 * @return int The expiration time in seconds.
	 */
	public static function update_transient_expiration() {
		return 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Returns the number of events associated with the layout.
	 *
	 * @since TBD
	 *
	 * @param string $layout_id The ID of the layout.
	 *
	 * @return int The number of posts associated with the layout.
	 */
	public static function get_associated_posts_by_id( string $layout_id ): int {
		try {
			$count = DB::table( 'posts', 'posts' )
						->leftJoin( 'postmeta', 'posts.id', 'layout_meta.post_id', 'layout_meta' )
						->where( 'meta_key', Meta::META_KEY_LAYOUT_ID )
						->where( 'meta_value', $layout_id )
						->count();
		} catch ( \Exception $e ) {
			$count = 0;
		}
		return $count;
	}

	/**
	 * Invalidates all the caches and custom tables storing information about Layouts.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function invalidate_cache(): bool {
		delete_transient( self::update_transient_name() );
		wp_cache_delete( 'option_format_layouts', 'tec-tickets-seating' );

		$invalidated = Layouts_Table::truncate() !== false;

		/**
		 * Fires after the caches and custom tables storing information about Layouts have been
		 * invalidated.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_seating_invalidate_layouts_cache' );

		return $invalidated;
	}
}

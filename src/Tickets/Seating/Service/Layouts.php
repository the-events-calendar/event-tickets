<?php
/**
 * The service component used to fetch the Layouts from the service.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Admin\Events\Associated_Events;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;
use TEC\Tickets\Seating\Admin\Tabs\Layout_Card;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Layouts.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Layouts {
	use oAuth_Token;
	use Logging;

	/**
	 * The URL to the service used to fetch the layouts from the backend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $service_fetch_url;

	/**
	 * Layouts constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 */
	public function __construct( string $backend_base_url ) {
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/layouts';
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since 5.16.0
	 *
	 * @param array<array{ id?: string, name?: string, seats?: int, mapId?: string, createdDate?: string, screenshotUrl?: string}> $service_rows The rows to insert.
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
				$created_date       = gmdate( 'Y-m-d H:i:s', intval( $created_date_in_ms / 1000 ) );

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
	 * @since 5.16.0
	 *
	 * @return array<array{id: string, name: string, seats: int}> The layouts in option format.
	 */
	public function get_in_option_format(): array {
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
	 * @since 5.16.0
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
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @return string The transient name used to store the last update time.
	 */
	public static function update_transient_name(): string {
		return 'tec_tickets_seating_layouts_last_update';
	}

	/**
	 * Returns the expiration time in seconds.
	 *
	 * @since 5.16.0
	 *
	 * @return int The expiration time in seconds.
	 */
	public static function update_transient_expiration() {
		return 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Returns the number of events associated with the layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The ID of the layout.
	 *
	 * @return int The number of posts associated with the layout.
	 */
	public static function get_associated_posts_by_id( string $layout_id ): int {
		global $wpdb;
		$ticketable_post_types = tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( empty( $ticketable_post_types ) ) {
			return 0;
		}

		$post_types = DB::prepare(
			implode( ', ', array_fill( 0, count( $ticketable_post_types ), '%s' ) ),
			...$ticketable_post_types
		);

		$supported_status_list = Associated_Events::get_supported_status_list();

		$status_list = DB::prepare(
			implode( ', ', array_fill( 0, count( $supported_status_list ), '%s' ) ),
			...$supported_status_list
		);

		try {
			$count = DB::get_var(
				DB::prepare(
					"SELECT COUNT(*) FROM %i AS posts
					LEFT JOIN %i AS layout_meta
					ON posts.ID = layout_meta.post_id
					WHERE posts.post_type IN ({$post_types})
					AND posts.post_status IN ({$status_list})
					AND layout_meta.meta_key = %s
					AND layout_meta.meta_value = %s",
					$wpdb->posts,
					$wpdb->postmeta,
					Meta::META_KEY_LAYOUT_ID,
					$layout_id
				)
			);
		} catch ( \Exception $e ) {
			$count = 0;
		}

		return $count;
	}

	/**
	 * Invalidates all the caches and custom tables storing information about Layouts.
	 *
	 * @since 5.16.0
	 *
	 * @param boolean $truncate Whether to truncate the caches and custom tables storing information about Layouts.
	 *
	 * @return bool Whether the caches and custom tables storing information about Layouts were invalidated.
	 */
	public static function invalidate_cache( bool $truncate = true ): bool {
		delete_transient( self::update_transient_name() );
		delete_transient( Seat_Types::update_transient_name() );
		wp_cache_delete( 'option_format_layouts', 'tec-tickets-seating' );
		$invalidated = true;

		if ( $truncate ) {
			$invalidated &= tribe( Layouts_Table::class )->empty_table() !== false &&
							tribe( Seat_Types_Table::class )->empty_table() !== false;
		}

		/**
		 * Fires after the caches and custom tables storing information about Layouts have been
		 * invalidated.
		 *
		 * @since 5.16.0
		 *
		 * @param boolean $truncate Whether to truncate the caches and custom tables storing information about Layouts.
		 */
		do_action( 'tec_tickets_seating_invalidate_layouts_cache', $truncate );

		return $invalidated;
	}

	/**
	 * Returns the URL to delete a layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The UUID of the layout to delete.
	 * @param string $map_id    The UUID of the map the layout belongs to.
	 *
	 * @return string The URL to delete the layout.
	 */
	public function get_delete_url( string $layout_id, string $map_id ): string {
		return add_query_arg(
			[
				'layoutId' => $layout_id,
				'mapId'    => $map_id,
			],
			$this->service_fetch_url
		);
	}

	/**
	 * Deletes a layout from the service.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The ID of the layout to delete.
	 * @param string $map_id    The Map ID of the layout to delete.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $layout_id, string $map_id ): bool {
		$url = $this->get_delete_url( $layout_id, $map_id );

		$args = [
			'method'  => 'DELETE',
			'headers' => [
				'Authorization' => 'Bearer ' . $this->get_oauth_token(),
				'Content-Type'  => 'application/json',
			],
		];

		$response = wp_remote_request( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( ! is_wp_error( $response ) && 200 === $code ) {
			self::invalidate_cache();
			Maps::invalidate_cache();
			return true;
		}

		$this->log_error(
			'Failed to delete the layout from the service.',
			[
				'source'   => __METHOD__,
				'code'     => $code,
				'url'      => $url,
				'response' => $response,
			]
		);

		return false;
	}

	/**
	 * Returns the URL to add a new layout.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map to add the layout to.
	 *
	 * @return string The URL to add a new layout.
	 */
	public function get_add_url( string $map_id ): string {
		return add_query_arg(
			[
				'map' => $map_id,
			],
			$this->service_fetch_url
		);
	}

	/**
	 * Adds a new layout to the service.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map to add the layout to.
	 *
	 * @return string|bool Layout ID on success, false on failure.
	 */
	public function add( string $map_id ) {
		$url = $this->get_add_url( $map_id );

		$args = [
			'method'  => 'POST',
			'headers' => [
				'Authorization' => 'Bearer ' . $this->get_oauth_token(),
				'Content-Type'  => 'application/json',
			],
		];

		$response = wp_remote_request( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 !== $code ) {
			$this->log_error(
				'Failed to Add new layout to the service.',
				[
					'source'   => __METHOD__,
					'code'     => $code,
					'url'      => $url,
					'response' => $response,
				]
			);
			return false;
		}

		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$layout_id = Arr::get( $body, [ 'data', 'items', 0, 'id' ] );

		self::invalidate_cache();
		Maps::invalidate_cache();
		return $layout_id;
	}

	/**
	 * Returns the URL to add a new layout.
	 *
	 * @since 5.17.0
	 *
	 * @param string $layout_id The ID of the map to add the layout to.
	 *
	 * @return string The URL for a layout duplication request.
	 */
	public function get_duplicate_url( string $layout_id ): string {
		return add_query_arg(
			[
				'layout' => $layout_id,
			],
			$this->service_fetch_url . '/duplicate'
		);
	}

	/**
	 * Duplicates a layout in the service.
	 *
	 * @since 5.17.0
	 *
	 * @param string $layout_id The ID of the layout to duplicate.
	 *
	 * @return string|bool Layout ID on success, false on failure.
	 */
	public function duplicate_layout( string $layout_id ) {
		$url = $this->get_duplicate_url( $layout_id );

		$args = [
			'method'  => 'POST',
			'headers' => [
				'Authorization' => 'Bearer ' . $this->get_oauth_token(),
				'Content-Type'  => 'application/json',
			],
		];

		$response = wp_remote_request( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || 200 !== $code ) {
			$this->log_error(
				'Failed to duplicate layout in the service.',
				[
					'source'   => __METHOD__,
					'code'     => $code,
					'url'      => $url,
					'response' => $response,
				]
			);
			return false;
		}

		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$layout_id = Arr::get( $body, [ 'data', 'items', 0, 'id' ] );

		self::invalidate_cache();
		Maps::invalidate_cache();

		return $layout_id;
	}

	/**
	 * Updates the capacity of all posts for the given layout IDs.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,int> $updates The layout ID to seats count map.
	 *
	 * @return int|false The number of posts updated, or `false` on failure.
	 */
	public function update_posts_capacity( array $updates ) {
		if ( empty( $updates ) ) {
			return 0;
		}

		$total_updated = 0;

		$ticketable_post_types = Tickets::instance()->post_types();
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		/*
		 * The number of posts to update is not known in advance: we cannot run an unbounded query to fetch the IDs to
		 * update.
		 * The Repository provides a query-behind-a-generator API that will allow us to write a readable, not unbounded,
		 * query to the database to fetch the IDs to update.
		 * The list of ticketable post types, though, is not known in advance.
		 * Here we create a Repository class that will be used to query the database for those post type.
		 * Thanks PHP 7+.
		 */
		$repository = new class( $ticketable_post_types ) extends \Tribe__Repository {
			/**
			 * @param string[] $post_types The list of ticketable post types.
			 */
			public function __construct( array $post_types ) {
				$this->default_args['post_type'] = $post_types;
				parent::__construct();
			}
		};

		foreach ( $updates as $layout_id => $seats ) {
			foreach (
				$repository->where( 'meta_equals', Meta::META_KEY_LAYOUT_ID, $layout_id )
							->get_ids( true ) as $post_id
			) {
				$previous_capacity = get_post_meta( $post_id, $capacity_meta_key, true );
				$capacity_delta    = $seats - (int) $previous_capacity;
				$previous_stock    = get_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, true );
				$new_stock         = max( 0, (int) $previous_stock + $capacity_delta );
				update_post_meta( $post_id, $capacity_meta_key, $seats );
				update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, $new_stock );
				++ $total_updated;
				// The reason we're not running a batch update is to have per-post cache control.
				clean_post_cache( $post_id );
			}

			// Update the Layout seats in the database.
			DB::update(
				Layouts_Table::table_name(),
				[ 'seats' => $seats ],
				[ 'id' => $layout_id ],
				[ '%d' ],
				[ '%s' ]
			);
		}

		// Finally, soft invalidate the layouts' caches.
		self::invalidate_cache( false );

		return $total_updated;
	}
}

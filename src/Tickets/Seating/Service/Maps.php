<?php
/**
 * The service component used to fetch the Maps from the service.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Tables\Maps as Maps_Table;
use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;

/**
 * Class Maps.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Maps {
	use oAuth_Token;
	use Logging;

	/**
	 * The URL to the service used to fetch the maps from the backend.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $service_fetch_url;

	/**
	 * A reference to the Layouts service facade.
	 *
	 * @since 5.16.0
	 *
	 * @var Layouts
	 */
	private Layouts $layouts;

	/**
	 * Maps constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 */
	public function __construct( string $backend_base_url, Layouts $layouts ) {
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/maps';
		$this->layouts = $layouts;
	}

	/**
	 * Invalidates the cache for the Maps.
	 *
	 * Note that, while likely required, this method will not invalidate the cache for the
	 * Layouts.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the cache was invalidated or not.
	 */
	public static function invalidate_cache(): bool {
		delete_transient( self::update_transient_name() );
		wp_cache_delete( 'option_map_card_objects', 'tec-tickets-seating' );

		$invalidated = tribe( Maps_Table::class )->empty_table() !== false;

		/**
		 * Fires after the caches and custom tables storing information about Maps have been
		 * invalidated.
		 *
		 * @since 5.16.0
		 */
		do_action( 'tec_tickets_seating_invalidate_maps_layouts_cache' );

		return $invalidated;
	}

	/**
	 * Fetches all the Maps from the database.
	 *
	 * @since 5.16.0
	 *
	 * @return Map_Card[] Array of map card objects.
	 */
	public function get_in_card_format() {
		if ( ! $this->update() ) {
			return [];
		}

		$cache_key = 'option_map_card_objects';
		$map_cards = wp_cache_get( $cache_key, 'tec-tickets-seating' );

		// Update the Layouts.
		$this->layouts->update( true );

		if ( ! ( $map_cards && is_array( $map_cards ) ) ) {
			$map_cards = [];
			foreach ( Maps_Table::fetch_all() as $row ) {
				$map_cards[] = new Map_Card(
					$row->id,
					$row->name,
					$row->seats,
					$row->screenshot_url,
					$this->map_has_layouts( $row->id ),
				);
			}

			wp_cache_set(
				$cache_key,
				$map_cards,
				'tec-tickets-seating',
				self::update_transient_expiration() // phpcs:ignore
			);
		}

		return $map_cards;
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since 5.16.0
	 *
	 * @param array<array{ id?: string, name?: string, seats?: int, screenshotUrl?: string}> $service_rows The rows to insert.
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
					// $service_row['screenshotUrl'] @todo still not provided by the service
				) ) {
					return $valid;
				}

				$valid[] = [
					'id'             => $service_row['id'],
					'name'           => $service_row['name'],
					'seats'          => $service_row['seats'],
					'screenshot_url' => $service_row['screenshotUrl'] ?? '',
				];

				return $valid;
			},
			[]
		);

		if ( ! count( $valid ) ) {
			return 0;
		}

		return Maps_Table::insert_many( $valid );
	}

	/**
	 * Updates the Maps from the service by updating the caches and custom tables.
	 *
	 * @since 5.16.0
	 *
	 * @param bool $force If true, the Maps will be updated even if they are up-to-date.
	 *
	 * @return bool Whether the Maps are up-to-date or not.
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
		return 'tec_tickets_seating_maps_last_update';
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
	 * Checks if the map has layouts.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map.
	 *
	 * @return bool The number of layouts.
	 */
	public function map_has_layouts( string $map_id ): bool {
		$count = DB::table( Layouts_Table::table_name( false ) )
					->where( 'map', $map_id )
					->count();

		return $count > 0;
	}

	/**
	 * Returns the URL to delete a map.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map to delete.
	 *
	 * @return string The URL to delete the map.
	 */
	public function get_delete_url( string $map_id ): string {
		return add_query_arg(
			[
				'mapId' => $map_id,
			],
			$this->service_fetch_url
		);
	}

	/**
	 * Deletes a map from the service.
	 *
	 * @since 5.16.0
	 *
	 * @param string $map_id The ID of the map.
	 *
	 * @return bool Whether the map was deleted or not.
	 */
	public function delete( string $map_id ): bool {
		// If the map has layouts, it should not be deleted.
		if ( $this->map_has_layouts( $map_id ) ) {
			return false;
		}

		$url = $this->get_delete_url( $map_id );

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
			Layouts::invalidate_cache();

			return true;
		}

		$this->log_error(
			'Failed to delete the map from the service.',
			[
				'source'   => __METHOD__,
				'code'     => $code,
				'url'      => $url,
				'response' => $response,
			]
		);

		return false;
	}
}

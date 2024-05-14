<?php
/**
 * The service component used to fetch the Maps from the service.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Admin\Tabs\Map_Card;
use TEC\Tickets\Seating\Tables\Maps as Maps_Table;

/**
 * Class Maps.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
class Maps {
	
	/**
	 * The URL to the service used to fetch the maps from the backend.
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
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/maps';
	}
	
	/**
	 * Fetches all the Maps from the database.
	 *
	 * @since TBD
	 *
	 * @return Map_Card[] Array of map card objects.
	 */
	public function get_in_card_format() {
		if ( ! $this->update() ) {
			return [];
		}
		
		$cache_key = 'option_map_card_objects';
		$map_cards = wp_cache_get( $cache_key, 'tec-tickets-seating' );
		
		if ( ! ( $map_cards && is_array( $map_cards ) ) ) {
			$map_cards = [];
			foreach ( Maps_Table::fetch_all() as $row ) {
				$map_cards[] = new Map_Card( $row['id'], $row['name'], $row['seats'], $row['screenshotUrl'] );
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
	 * @since TBD
	 *
	 * @param array<array{
	 *     id?: string,
	 *     name?: string,
	 *     seats?: int,
	 *     screenshotUrl?: string,
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
					$service_row['screenshotUrl']
				) ) {
					return $valid;
				}
				
				$valid[] = [
					'id'            => $service_row['id'],
					'name'          => $service_row['name'],
					'seats'         => $service_row['seats'],
					'screenshotUrl' => $service_row['screenshotUrl'],
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
						->update_from_service(
							function () {
								wp_cache_delete( 'option_map_card_objects', 'tec-tickets-seating' );
								Maps_Table::truncate();
							}
						)
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
		return 'tec_tickets_seating_maps_last_update';
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
}

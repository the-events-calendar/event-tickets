<?php
/**
 * The service component used to fetch the Layouts from the service.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Tables\Layouts as Layouts_Table;

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
	 * since TBD
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
	 * }> $service_rows
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
					$service_row['createdDate']
				) ) {
					return $valid;
				}

				$valid[] = [
					'id'           => $service_row['id'],
					'name'         => $service_row['name'],
					'seats'        => $service_row['seats'],
					'map'          => $service_row['mapId'],
					'created_date' => $service_row['createdDate'],
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
				self::update_transient_expiration()
			);
		}

		return $layouts;
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

		$updted = $updater->check_last_update( $force )
		                  ->update_from_service( fn() => Layouts_Table::truncate() )
		                  ->store_fetched_data( [ $this, 'insert_rows_from_service' ] );

		wp_cache_delete( 'option_format_layouts', 'tec-tickets-seating' );

		return $updted;
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
}
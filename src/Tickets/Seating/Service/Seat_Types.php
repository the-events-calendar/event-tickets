<?php
/**
 * The service component used to fetch the Seat Types from the service.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;

/**
 * Class Seat_Types.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
class Seat_Types {
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
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/seat-types';
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since TBD
	 *
	 * @param array<array{
	 *     id?: string,
	 *     name?: string,
	 *     mapId?: string,
	 *     layoutId?: string,
	 *     seats?: int
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
					$service_row['mapId'],
					$service_row['layoutId'],
					$service_row['seats']
				) ) {
					return $valid;
				}

				$valid[] = [
					'id'     => $service_row['id'],
					'name'   => $service_row['name'],
					'map'    => $service_row['mapId'],
					'layout' => $service_row['layoutId'],
					'seats'  => $service_row['seats'],
				];

				return $valid;
			},
			[]
		);

		if ( ! count( $valid ) ) {
			return 0;
		}

		return Seat_Types_Table::insert_many( $valid );


	}

	/**
	 * Returns the seat types in option format.
	 *
	 * @since TBD
	 *
	 * @return array<string, array{id: string, name: string, seats: int}> The seat types in option format.
	 */
	public function get_in_option_format(): array {
		if ( ! $this->update() ) {
			return [];
		}

		$seat_types = wp_cache_get( 'option_format_seat_types', 'tec-tickets-seating' );

		if ( ! ( $seat_types && is_array( $seat_types ) ) ) {
			$seat_types = [];
			foreach ( Seat_Types_Table::fetch_all() as $row ) {
				$seat_types[] = [
					'id'    => $row->id,
					'name'  => $row->name,
					'seats' => $row->seats,
				];
			}

			wp_cache_set(
				'option_format_seat_types',
				$seat_types,
				'tec-tickets-seating',
				self::update_transient_expiration()
			);
		}

		return $seat_types;
	}

	/**
	 * Updates the seat types from the service by updating the caches and custom tables.
	 *
	 * @since TBD
	 *
	 * @param bool $force If true, the seat types will be updated even if they are up-to-date.
	 *
	 * @return bool Whether the seat types are up-to-date or not.
	 */
	public function update( bool $force = false ): bool {
		$updater = new Updater( $this->service_fetch_url, self::update_transient_name(), self::update_transient_expiration() );

		$updated= $updater->check_last_update( $force )
		               ->update_from_service( fn() => Seat_Types_Table::truncate() )
		               ->store_fetched_data( [ $this, 'insert_rows_from_service' ] );

		wp_cache_delete( 'option_format_seat_types', 'tec-tickets-seating' );

		return $updated;
	}

	/**
	 * Returns the transient name used to store the last update time.
	 *
	 * @since TBD
	 *
	 * @return string
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
	public static function update_transient_expiration(): int {
		return 12 * HOUR_IN_SECONDS;
	}
}
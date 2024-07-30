<?php
/**
 * The service component used to fetch the Seat Types from the service.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;

/**
 * Class Seat_Types.
 *
 * @since   TBD
 *
 * @package TEC\Controller\Service;
 */
class Seat_Types {
	use Logging;

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
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/seat-types';
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since TBD
	 *
	 * @param array $service_rows {
	 *    The list of seat types to insert.
	 *
	 *      @type string $id The seat type ID.
	 *      @type string $name The seat type name.
	 *      @type string $mapId The map ID the seat type belongs to.
	 *      @type string $layoutId The layout ID the seat type belongs to.
	 *      @type int $seats The number of seats in the seat type.
	 * }
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
	 * @param string[] $layout_ids The layout IDs to get the seat types for.
	 *
	 * @return array<string, array{id: string, name: string, seats: int}> The seat types in option format.
	 */
	public function get_in_option_format( array $layout_ids ): array {
		if ( ! $this->update() ) {
			return [];
		}

		$seat_types              = [];
		$layout_ids_placeholders = implode( ',', array_fill( 0, count( $layout_ids ), '%s' ) );
		$layout_ids_interval     = DB::prepare( $layout_ids_placeholders, ...$layout_ids );
		/** @var object{id: string, name: string, seats: int} $row */
		foreach ( Seat_Types_Table::fetch_all_where( "WHERE layout IN ({$layout_ids_interval})" ) as $row ) {
			$seat_types[] = [
				'id'    => $row->id,
				'name'  => $row->name,
				'seats' => $row->seats,
			];
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
		$updater = new Updater(
			$this->service_fetch_url,
			self::update_transient_name(),
			self::update_transient_expiration()
		);

		$updated = $updater->check_last_update( $force )
							->update_from_service( fn() => Seat_Types_Table::truncate() )
							->store_fetched_data( [ $this, 'insert_rows_from_service' ] );

		return $updated;
	}

	/**
	 * Returns the transient name used to store the last update time.
	 *
	 * @since TBD
	 *
	 * @return string The transient name used to store the last update time.
	 */
	public static function update_transient_name(): string {
		return 'tec_tickets_seating_seat_types_last_update';
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

	/**
	 * Updates the seat types from the service by updating the custom table.
	 *
	 * @since TBD
	 *
	 * @param array<array<string,string>> $seat_types The data of the seat types to update.
	 *
	 * @return int|false The number of seat types updated, or `false` on failure.
	 */
	public function update_from_service( array $seat_types ) {
		$total_updated = 0;

		foreach ( $seat_types as $seat_type ) {
			$id    = $seat_type['id'];
			$name  = $seat_type['name'];
			$seats = $seat_type['seatsCount'];

			try {
				$updated = DB::query(
					DB::prepare(
						'UPDATE %i SET name = %s, seats = %d WHERE id = %s',
						Seat_Types_Table::table_name(),
						$name,
						$seats,
						$id
					),
				);
			} catch ( \Exception $e ) {
				$this->log_error(
					'Failed to update the seat types from the service.',
					[
						'source' => __METHOD__,
						'error'  => $e->getMessage(),
					]
				);
				return false;
			}

			if ( false === $updated ) {
				return false;
			}

			$total_updated += $updated;
		}

		return $total_updated;
	}

	/**
	 * Updates the capacity of all tickets for the seat types.
	 *
	 * @since TBD
	 *
	 * @param array<string,int> $updates The seat type ID to capacity map.
	 *
	 * @return int The number of tickets updated, or `false` on failure.
	 */
	public function update_tickets_capacity( array $updates ): int {
		if ( empty( $updates ) ) {
			return 0;
		}

		$total_updated = 0;

		$seat_types = array_keys( $updates );
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		foreach (
			tribe_tickets()
				->where( 'meta_in', Meta::META_KEY_SEAT_TYPE, $seat_types )
				->get_ids( true ) as $ticket_id
		) {
			$seat_type_id      = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );
			$new_capacity      = $updates[ $seat_type_id ];
			$previous_capacity = get_post_meta( $ticket_id, $capacity_meta_key, true );
			$capacity_delta    = $new_capacity - $previous_capacity;
			$previous_stock    = get_post_meta( $ticket_id, '_stock', true );
			$new_stock         = max( 0, $previous_stock + $capacity_delta );
			update_post_meta( $ticket_id, $capacity_meta_key, $new_capacity );
			update_post_meta( $ticket_id, '_stock', $new_stock );
			clean_post_cache( $ticket_id );
			++$total_updated;
		}

		return $total_updated;
	}
}

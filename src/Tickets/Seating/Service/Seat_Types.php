<?php
/**
 * The service component used to fetch the Seat Types from the service.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use Exception;
use stdClass;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Tables\Seat_Types as Seat_Types_Table;
use Tribe__Tickets__Tickets_Handler;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Seating\Commerce\Controller as Commerce_Controller;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Seat_Types.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Seat_Types {
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
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/seat-types';
	}

	/**
	 * Inserts multiple rows from the service into the table.
	 *
	 * @since 5.16.0
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
	 * @since 5.16.0
	 *
	 * @param string[] $layout_ids The layout IDs to get the seat types for.
	 *
	 * @return array<array{id: string, name: string, seats: int}> The seat types in option format.
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

		// Order seat types by name.
		usort(
			$seat_types,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $seat_types;
	}

	/**
	 * Updates the seat types from the service by updating the caches and custom tables.
	 *
	 * @since 5.16.0
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
							->update_from_service( fn() => tribe( Seat_Types_Table::class )->empty_table() )
							->store_fetched_data( [ $this, 'insert_rows_from_service' ] );

		return $updated;
	}

	/**
	 * Returns the transient name used to store the last update time.
	 *
	 * @since 5.16.0
	 *
	 * @return string The transient name used to store the last update time.
	 */
	public static function update_transient_name(): string {
		return 'tec_tickets_seating_seat_types_last_update';
	}

	/**
	 * Returns the expiration time in seconds.
	 *
	 * @since 5.16.0
	 *
	 * @return int The expiration time in seconds.
	 */
	public static function update_transient_expiration(): int {
		return 12 * HOUR_IN_SECONDS;
	}

	/**
	 * Updates the seat types from the service by updating the custom table.
	 *
	 * @since 5.16.0
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
			} catch ( Exception $e ) {
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
	 * @since 5.16.0
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
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		/*
		 * The Commerce controller will, on update of the `_stock` meta, trigger a cross-update of all Tickets sharing the
		 * same seat type: we're doing this here so the action should not fire.
		 */
		remove_action( 'update_post_metadata', [ tribe( Commerce_Controller::class ), 'handle_ticket_meta_update' ] );

		foreach (
			tribe_tickets()
				->where( 'meta_in', Meta::META_KEY_SEAT_TYPE, $seat_types )
				->get_ids( true ) as $ticket_id
		) {
			clean_post_cache( $ticket_id );

			$seat_type_id      = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );
			$new_capacity      = $updates[ $seat_type_id ];
			$previous_capacity = get_post_meta( $ticket_id, $capacity_meta_key, true );
			$capacity_delta    = $new_capacity - $previous_capacity;
			$previous_stock    = get_post_meta( $ticket_id, '_stock', true );
			$new_stock         = max( 0, $previous_stock + $capacity_delta );
			update_post_meta( $ticket_id, $capacity_meta_key, $new_capacity );

			update_post_meta( $ticket_id, '_stock', $new_stock );

			++$total_updated;
		}

		add_action( 'update_post_metadata', [ tribe( Commerce_Controller::class ), 'handle_ticket_meta_update' ], 10, 4 );

		return $total_updated;
	}

	/**
	 * Updates the tickets moving to the with the $new_seat_type_id with the calculated stock and capacity.
	 *
	 * @since 5.16.0
	 *
	 * @param string     $new_seat_type_id    The new seat type ID.
	 * @param int        $new_capacity        The new capacity.
	 * @param array<int> $original_ticket_ids The original ticket IDs that belonged to the new seat type prior this update.
	 *
	 * @return int The number of tickets updated.
	 */
	public function update_tickets_with_calculated_stock_and_capacity( string $new_seat_type_id, int $new_capacity, array $original_ticket_ids ): int {
		if ( empty( $new_seat_type_id ) || empty( $new_capacity ) ) {
			return 0;
		}

		$total_updated = 0;

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		$events_primary = [];

		remove_action( 'update_post_metadata', [ tribe( Commerce_Controller::class ), 'handle_ticket_meta_update' ] );

		foreach (
			tribe_tickets()
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $new_seat_type_id )
				->not_in( ! empty( $original_ticket_ids ) ? $original_ticket_ids : 0 )
				->get_ids( true ) as $ticket_id
		) {
			clean_post_cache( $ticket_id );

			$event_id = get_post_meta( $ticket_id, Ticket::$event_relation_meta_key, true );

			$primary_seat_type_ticket = tribe_tickets()
				->where( 'event', $event_id )
				->where( 'meta_equals', Meta::META_KEY_SEAT_TYPE, $new_seat_type_id )
				->in( ! empty( $original_ticket_ids ) ? $original_ticket_ids : 0 )
				->first();

			if ( empty( $primary_seat_type_ticket->ID ) ) {
				/**
				 * In this case, since the seat types' capacity is not present already in the event.
				 * The event's capacity is going to change because of the new seat type becoming a part of the event.
				 */
				$previous_capacity = get_post_meta( $ticket_id, $capacity_meta_key, true );
				$capacity_delta    = $new_capacity - $previous_capacity;
				$previous_stock    = get_post_meta( $ticket_id, '_stock', true );
				$new_stock         = max( 0, $previous_stock + $capacity_delta );

				// Update ticket's stock and capacity.
				update_post_meta( $ticket_id, $capacity_meta_key, $new_capacity );
				update_post_meta( $ticket_id, '_stock', $new_stock );

				// Update event's stock and capacity.
				$old_stock_level = (int) get_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, true );
				$old_capacity    = (int) get_post_meta( $event_id, $capacity_meta_key, true );

				// The order of the updates is too IMPORTANT here! Don't change it or you'll introduce a bug unless you remove a filter attached to capacity's update.
				update_post_meta(
					$event_id,
					$capacity_meta_key,
					$old_capacity + $capacity_delta
				);
				update_post_meta(
					$event_id,
					Global_Stock::GLOBAL_STOCK_LEVEL,
					$old_stock_level + $capacity_delta
				);

				// Count this as just a ticket update.
				++$total_updated;
				continue;
			}

			// In case there are previous tickets with the seat type we are updating to, we should depend on them.
			$old_seat_types_ticket_stock = get_post_meta( $primary_seat_type_ticket->ID, Ticket::$stock_meta_key, true );
			$new_seat_types_ticket_stock = get_post_meta( $ticket_id, Ticket::$stock_meta_key, true );

			$new_stock = $old_seat_types_ticket_stock + $new_seat_types_ticket_stock;

			update_post_meta( $ticket_id, Ticket::$stock_meta_key, $new_stock );
			update_post_meta( $ticket_id, $capacity_meta_key, $new_capacity );

			$events_primary[ $event_id ] = [ $new_stock, $new_capacity ];

			++$total_updated;
		}

		foreach ( $original_ticket_ids as $primary_seat_type_ticket_id ) {
			$primary_event_id = get_post_meta( $primary_seat_type_ticket_id, Ticket::$event_relation_meta_key, true );

			if ( ! isset( $events_primary[ $primary_event_id ] ) ) {
				/**
				 * Here we have events without the old seat type but the one being updated was already present.
				 *
				 * So we need to handle both the event's capacity and stock and the tickets!
				 *
				 * We need to be careful here!
				 */

				$previous_capacity = get_post_meta( $primary_seat_type_ticket_id, $capacity_meta_key, true );
				$capacity_delta    = $new_capacity - $previous_capacity;

				update_post_meta(
					$primary_seat_type_ticket_id,
					Ticket::$stock_meta_key,
					(int) get_post_meta( $primary_seat_type_ticket_id, Ticket::$stock_meta_key, true ) + $capacity_delta
				);
				update_post_meta( $primary_seat_type_ticket_id, $capacity_meta_key, $new_capacity );

				$old_stock_level = (int) get_post_meta( $primary_event_id, Global_Stock::GLOBAL_STOCK_LEVEL, true );
				$old_capacity    = (int) get_post_meta( $primary_event_id, $capacity_meta_key, true );

				update_post_meta(
					$primary_event_id,
					$capacity_meta_key,
					$old_capacity + $capacity_delta
				);
				update_post_meta(
					$primary_event_id,
					Global_Stock::GLOBAL_STOCK_LEVEL,
					$old_stock_level + $capacity_delta
				);

				// Count the ticked update.
				++$total_updated;
				continue;
			}

			$stock_capacity = $events_primary[ $primary_event_id ];

			update_post_meta( $primary_seat_type_ticket_id, Ticket::$stock_meta_key, $stock_capacity[0] );
			update_post_meta( $primary_seat_type_ticket_id, $capacity_meta_key, $stock_capacity[1] );

			// Count the updates taking place in the tickets using originally the seat type as well.
			++$total_updated;
		}

		add_action( 'update_post_metadata', [ tribe( Commerce_Controller::class ), 'handle_ticket_meta_update' ], 10, 4 );

		return $total_updated;
	}

	/**
	 * Returns the primary seat type for a given layout.
	 *
	 * The primary seat type is the first seat type in `id` order.
	 *
	 * @since 5.16.0
	 *
	 * @param string $layout_id The layout ID to fetch the primary seat type for.
	 *
	 * @return stdClass|null The primary seat type, or `null` if no seat types are found.
	 */
	public function get_primary_seat_type( string $layout_id ): ?stdClass {
		if ( ! $this->update() ) {
			return null;
		}

		return Seat_Types_Table::fetch_first_where(
			DB::prepare(
				'WHERE layout = %s ORDER BY id ASC',
				$layout_id
			),
			OBJECT
		);
	}
}

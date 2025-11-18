<?php
/**
 * The Seat Types table schema.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

/**
 * Class Seat_Types.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Tables;
 */
class Seat_Types extends Table {
	/**
	 * The schema version.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_seat_types';

	/**
	 * The table group.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-seat-types';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since 5.27.0
	 *
	 * @var string[]
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = ( new String_Column( 'id' ) )->set_length( 36 )->set_is_primary_key( true );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 255 );
				$columns[] = ( new String_Column( 'map' ) )->set_length( 36 );
				$columns[] = ( new String_Column( 'layout' ) )->set_length( 36 );
				$columns[] = ( new Integer_Column( 'seats' ) )->set_length( 11 )->set_default( 0 );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since 5.16.0
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				`id` varchar(36) NOT NULL,
				`name` varchar(255) NOT NULL,
				`map` varchar(36) NOT NULL,
				`layout` varchar(36) NOT NULL,
				`seats` int(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			) {$charset_collate};
		";
	}

	/**
	 * Returns the number of seats for a given seat type.
	 *
	 * @since 5.16.0
	 *
	 * @param string $seat_type The seat type UUID to return the number of seats for.
	 *
	 * @return int The number of seats for the given seat type. If the seat type does not exist, returns `0`.
	 */
	public function get_seats( string $seat_type ): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT seats FROM %i WHERE id = %s',
				self::table_name(),
				$seat_type
			)
		);
	}
}

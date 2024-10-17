<?php
/**
 * The Seat Types table schema.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Seat_Types.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */
class Seat_Types extends Table {
	use Custom_Table_Query_Methods;

	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_seat_types';

	/**
	 * The table group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-seat-types';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';

	/**
	 * Returns the number of seats for a given seat type.
	 *
	 * @since TBD
	 *
	 * @param string $seat_type The seat type UUID to return the number of seats for.
	 *
	 * @return int The number of seats for the given seat type. If the seat type does not exist, returns `0`.
	 */
	public function get_seats( string $seat_type ): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT seats FROM %i WHERE id = %s',
				self::table_name( true ),
				$seat_type
			)
		);
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since TBD
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	protected function get_definition() {
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
}

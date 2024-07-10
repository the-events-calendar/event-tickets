<?php
/**
 * Provides the truncate methods for the Seating tables.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\DB\DB;

/**
 * Trait Truncate_Methods.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Tables;
 */
trait Truncate_Methods {
	/**
	 * Truncates the table.
	 *
	 * @since TBD
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function truncate() {
		return DB::query(
			DB::prepare(
				"TRUNCATE TABLE %i",
				static::table_name( true )
			)
		);
	}
}
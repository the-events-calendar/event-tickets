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
		DB::query( 'SET FOREIGN_KEY_CHECKS = 0;' );
		$deleted = DB::query(
			DB::prepare(
				'DELETE FROM %i',
				static::table_name( true )
			)
		);
		DB::query( 'SET FOREIGN_KEY_CHECKS = 1;' );

		return $deleted;
	}
}

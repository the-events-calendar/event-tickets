<?php

namespace TEC\Tickets\Seating\Tests\Integration;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Maps;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tables\Sessions;

trait Truncates_Custom_Tables {
	/**
	 * @before
	 * @after
	 */
	public function truncate_tables(): void {
		foreach([
			Maps::table_name(),
			Seat_Types::table_name(),
			Layouts::table_name(),
			Sessions::table_name(),
		] as $table_name){
			DB::query(
				DB::prepare(
					"DELETE FROM %i",
					$table_name
				)
			);
		}
	}

}

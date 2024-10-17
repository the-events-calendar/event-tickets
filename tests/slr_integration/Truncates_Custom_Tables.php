<?php

namespace TEC\Tickets\Seating\Tests\Integration;

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
		Maps::truncate();
		Seat_Types::truncate();
		Layouts::truncate();
		Sessions::truncate();
	}

}

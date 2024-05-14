<?php
/**
 * The service component used to fetch the Maps from the service.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Tables\Maps as Maps_Table;

/**
 * Class Maps.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
class Maps {
	
	/**
	 * Fetches all the Maps from the database.
	 *
	 * @since TBD
	 *
	 * @return array<string, array{id: string, name: string, seats: int}> The map rows in array format.
	 */
	public static function fetch_all() {
		return Maps_Table::fetch_all();
	}
}
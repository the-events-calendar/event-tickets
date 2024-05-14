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

class Maps {
	public static function fetch_all() {
		return Maps_Table::fetch_all();
	}
}
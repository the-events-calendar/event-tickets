<?php
/**
 * The custom tables' controller.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\StellarWP\Schema\Register;

/**
 * Class Tables.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */
class Tables extends Controller_Contract {

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
	}

	/**
	 * Registers the tables and the bindings required to use them.
	 *
	 * @since TBD
	 *
	 * @return void The tables are registered.
	 */
	protected function do_register(): void {
		Register::table( Tables\Layouts::class );
		Register::table( Tables\Seat_Types::class );
	}
}
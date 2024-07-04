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
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Seating\Tables\Sessions;

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
		remove_actions( 'tec_tickets_seating_tables_cron', [ Sessions::class, 'remove_expired_sessions' ] );
		wp_clear_scheduled_hook('tec_tickets_seating_tables_cron');
	}

	/**
	 * Registers the tables and the bindings required to use them.
	 *
	 * @since TBD
	 *
	 * @return void The tables are registered.
	 */
	protected function do_register(): void {
		Register::table( Tables\Maps::class );
		Register::table( Tables\Layouts::class );
		Register::table( Tables\Seat_Types::class );
		Register::table( Tables\Sessions::class );

		if ( ! wp_next_scheduled( 'tec_tickets_seating_tables_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'tec_tickets_seating_tables_cron' );
		}

		add_action( 'tec_tickets_seating_tables_cron', [ Sessions::class, 'remove_expired_sessions' ] );
	}
}
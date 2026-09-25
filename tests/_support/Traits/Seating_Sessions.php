<?php

namespace Tribe\Tickets\Test\Traits;

use TEC\Tickets\Seating\Tables\Sessions;

trait Seating_Sessions {
	/**
	 * Opens a seat selection session the way a visitor does.
	 *
	 * Rendering the seat selection modal records the token the Seating service issued, carrying that
	 * token's own lifetime; starting the timer is the visitor's first deliberate act and shortens the
	 * row to the seat timeout. Endpoints that hold seats only accept a session that reached the second
	 * step, so a fixture that stops at the first is not a usable session.
	 *
	 * @param string   $token      The session token.
	 * @param int      $object_id  The post the token was issued for.
	 * @param int|null $expiration The timestamp the started session should expire at, or null for one
	 *                             far enough ahead to outlast the run.
	 */
	protected function given_a_started_session( string $token, int $object_id, ?int $expiration = null ): void {
		/*
		 * The column stores a signed int, so a date past 2038 clamps on write and the start_timer
		 * guard can then never fire, leaving the session silently unstarted.
		 */
		$expiration = $expiration ?? strtotime( '2030-01-01 00:00:00' );
		$sessions   = tribe( Sessions::class );
		$sessions->insert_or_update( $token, $object_id, $expiration + HOUR_IN_SECONDS );
		$sessions->start_timer( $token, $object_id, $expiration );
	}
}

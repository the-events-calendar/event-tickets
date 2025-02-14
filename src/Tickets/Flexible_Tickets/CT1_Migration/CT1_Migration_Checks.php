<?php
/**
 * Provides methods for classes that have to run checks in the context of the integration of Flexible
 * Tickets with the Custom Tables v1 migration.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration;

use Tribe__Tickets__RSVP as RSVP;

/**
 * Class CT1_Migration_Checks.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration;
 */
trait CT1_Migration_Checks {

	/**
	 * Checks whether an Event has at least one RSVP ticket assigned.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @return bool Whether the Event has at least one RSVP ticket assigned.
	 */
	protected function has_rsvp_tickets( int $post_id ): bool {
		return tribe_tickets( 'rsvp' )->where( 'event', $post_id )->count() > 0;
	}

	/**
	 * Returns the post IDs of the tickets attached to the Event, excluding RSVP tickets.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @return int[] The post IDs of the tickets attached to the Event.
	 */
	protected function get_ticket_ids( int $post_id ): array {
		$remove_rsvp_tickets = static function ( array $modules ): array {
			unset( $modules[ RSVP::class ] );

			return $modules;
		};

		add_filter( 'tribe_tickets_get_modules', $remove_rsvp_tickets );
		$ticket_ids = tribe_tickets()->where( 'event', $post_id )->get_ids();
		remove_filter( 'tribe_tickets_get_modules', $remove_rsvp_tickets );

		return $ticket_ids;
	}

	/**
	 * Checks whether an Event has at least one ticket assigned that is not an RSVP ticket.
	 *
	 * The method will temporarily remove the RSVP module from the list of available modules, to avoid
	 * the RSVP tickets being considered as regular tickets.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @return bool Whether the Event has at least one non-RSVP ticket assigned.
	 */
	protected function has_tickets( int $post_id ): bool {
		return count( $this->get_ticket_ids( $post_id ) ) > 0;
	}
}

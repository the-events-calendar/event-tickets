<?php
/**
 * Handles the integration between Flexible Tickets and the The Events Calendar Custom Tables v1 migration.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Strategy_Interface;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\RSVP_Ticketed_Recurring_Event_Strategy;

/**
 * Class CT1_Migration.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class CT1_Migration extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_events_custom_tables_v1_migration_strategy', [ $this, 'alter_migration_strategy' ], 10, 3 );
		add_filter( 'tec_events_custom_tables_v1_migration_strings', [ $this, 'add_migration_strings' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_events_custom_tables_v1_migration_strategy', [ $this, 'alter_migration_strategy' ] );
		remove_filter( 'tec_events_custom_tables_v1_migration_strings', [ $this, 'add_migration_strings' ] );
	}

	/**
	 * Checks whether an Event has at least one RSVP ticket assigned.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @return bool Whether the Event has at least one RSVP ticket assigned.
	 */
	private function has_rsvp_tickets( int $post_id ): bool {
		return tribe_tickets( 'rsvp' )->where( 'event', $post_id )->count() > 0;
	}

	/**
	 * Alters the migration strategy for an Event if ticketed.
	 *
	 * @since TBD
	 *
	 * @param Strategy_Interface|null $strategy The current strategy.
	 * @param int                     $post_id  The ID of the Event.
	 * @param bool                    $dry_run  Whether the migration should actually commit information,
	 *
	 * @return Strategy_Interface|null The altered strategy, if required.
	 */
	public function alter_migration_strategy( ?Strategy_Interface $strategy, int $post_id, bool $dry_run ): ?Strategy_Interface {
		if ( tribe_is_recurring_event( $post_id ) && $this->has_rsvp_tickets( $post_id ) ) {
			return new RSVP_Ticketed_Recurring_Event_Strategy( $post_id, $dry_run );
		}

		return $strategy;
	}

	/**
	 * Filters the CT1 Migration strings dictionary map to add strings for this plugin.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $map A map of strings from slugs to their localized versions.
	 *
	 * @return array<string,string> The altered map.
	 */
	public function add_migration_strings( array $map ): array {
		$map ['migration-error-recurring-with-rsvp-tickets'] = _x(
			'The event %s cannot be migrated because we do not support RSVPs on recurring events. Remove the ' .
			'RSVPs or convert the occurrences to single events (%sRead more%s).',
			'The error message displayed when a recurring event with RSVP tickets is being migrated.',
			'event-tickets'
		);

		return $map;
	}
}
<?php
/**
 * Handles the integration between Flexible Tickets and the The Events Calendar Custom Tables v1 migration.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events\Custom_Tables\V1\Migration\State;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Strategy_Interface;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Multi_Rule_Event_Migration_Strategy;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\CT1_Migration_Checks;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\RSVP_Ticketed_Recurring_Event_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Multi_Rule_Event_Migration_Strategy;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies\Ticketed_Single_Rule_Event_Migration_Strategy;

/**
 * Class CT1_Migration.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class CT1_Migration extends Controller {
	use CT1_Migration_Checks;

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_events_custom_tables_v1_migration_strategy', [ $this, 'alter_migration_strategy' ], 10, 3 );
		add_filter( 'tec_events_custom_tables_v1_migration_strings', [ $this, 'add_migration_strings' ] );
		add_filter( 'tec_events_custom_tables_v1_migration_event_report_categories', [
			$this,
			'filter_report_headers'
		], 30, 2 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_events_custom_tables_v1_migration_strategy', [ $this, 'alter_migration_strategy' ] );
		remove_filter( 'tec_events_custom_tables_v1_migration_strings', [ $this, 'add_migration_strings' ] );
		remove_filter( 'tec_events_custom_tables_v1_migration_event_report_categories', [
			$this,
			'filter_report_headers'
		], 30 );
	}

	/**
	 * Counts the number of recurrence rules (RRULE in iCalendar format) of an Event.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @return int The number of recurrence rules.
	 */
	private function count_rrules( int $post_id ): int {
		$recurrence_meta = get_post_meta( $post_id, '_EventRecurrence', true );

		return count( $recurrence_meta['rules'] ?? [] );

	}

	/**
	 * Alters the migration strategy for an Event if ticketed.
	 *
	 * @since 5.8.0
	 *
	 * @param Strategy_Interface|null $strategy The current strategy.
	 * @param int                     $post_id  The ID of the Event.
	 * @param bool                    $dry_run  Whether the migration should actually commit information,
	 *
	 * @return Strategy_Interface|null The altered strategy, if required.
	 */
	public function alter_migration_strategy( ?Strategy_Interface $strategy, int $post_id, bool $dry_run ): ?Strategy_Interface {
		if ( ! ( tribe_is_recurring_event( $post_id ) ) ) {
			// Only deal with Recurring Events.
			return $strategy;
		}

		if ( $this->has_rsvp_tickets( $post_id ) ) {
			// Migration of Recurring Events with RSVP tickets is always blocked; no matter other tickets.
			return new RSVP_Ticketed_Recurring_Event_Strategy( $post_id, $dry_run );
		}


		if ( $this->has_tickets( $post_id ) ) {
			// Use different strategies depending on the number of recurrence rules.
			if ( $this->count_rrules( $post_id ) === 1 ) {
				return new Ticketed_Single_Rule_Event_Migration_Strategy( $post_id, $dry_run );
			}

			return new Ticketed_Multi_Rule_Event_Migration_Strategy( $post_id, $dry_run );
		}

		return $strategy;
	}

	/**
	 * Filters the CT1 Migration strings dictionary map to add strings for this plugin.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,string> $map A map of strings from slugs to their localized versions.
	 *
	 * @return array<string,string> The altered map.
	 */
	public function add_migration_strings( array $map ): array {
		$map ['migration-error-recurring-with-rsvp-tickets']                                               = _x(
		// translators: %1$s is the singular label for events, %2$s is the event title, %3$s and %4$s are HTML tags for a link.
			sprintf(
				'The %1$s %2$s cannot be migrated because we do not support RSVPs on recurring events. Remove the ' .
				'RSVPs or convert the occurrences to single events (%3$sRead more%4$s).',
				tribe_get_event_label_singular_lowercase(),
				'%1$s', // A trick to make this placeholder survive the sprintf and store, when compiled, the title.
				'<a href="https://evnt.is/r-rsvp" target="_blank">',
				'</a>'
			),
			'The error message displayed when a recurring event with RSVP tickets is being migrated.',
			'event-tickets'
		);
		$map['migration-failure-series-not-found']                                                         = _x(
			'The event %s generated an error: cannot find Series for Event.',
			'The error message displayed when a recurring event with tickets is being migrated and the Series cannot be found.',
			'event-tickets'
		);
		$map[ 'migration-prompt-strategy-' . Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ]   = _x(
		// translators: %1$s is the plural label for events, %2$s and %3$s are HTML tags for a link.
			sprintf(
				'The following recurring %1$s will be part of a new Series of the same name, and tickets will ' .
				'be converted to %2$sSeries Passes%3$s:',
				tribe_get_event_label_plural_lowercase(),
				'<a href="https://evnt.is/sp-migration" target="_blank">',
				'</a>'
			),
			'The header message displayed to preview the migration of ticketed Recurring Events with 1 recurrence rule.',
			'event-tickets'
		);
		$map[ 'migration-complete-strategy-' . Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ] = _x(
		// translators: %1$s is the plural label for events, %2$s and %3$s are HTML tags for a link.
			sprintf(
				'The following recurring %1$s  are now part of a new Series of the same name. Ticket(s) have ' .
				'been converted to %2$sSeries Passes%3$s:',
				tribe_get_event_label_plural_lowercase(),
				'<a href="https://evnt.is/sp-migration" target="_blank">',
				'</a>'
			),
			'The header message displayed after the migration of ticketed Recurring Events with 1 recurrence rule.',
			'event-tickets'
		);
		$map[ 'migration-prompt-strategy-' . Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug() ]    = _x(
		// translators: %1$s and %2$s are plural and singular labels for events, %3$s and %4$s are HTML tags for a link.
			sprintf(
				'The following %1$s have multiple recurrence rules and will be split into multiple recurring %1$s ' .
				'with identical content. Each recurring %2$s will be part of a new Series of the same name, and tickets ' .
				'will be converted to %3$sSeries Passes%4$s:',
				tribe_get_event_label_plural_lowercase(),
				tribe_get_event_label_singular_lowercase(),
				'<a href="https://evnt.is/sp-migration" target="_blank">',
				'</a>'
			),
			'The header message displayed to preview the migration of ticketed Recurring Events with multiple recurrence rules.',
			'event-tickets'
		);
		$map[ 'migration-complete-strategy-' . Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug() ]  = _x(
		// translators: %1$s and %2$s are plural and singular labels for events, %3$s and %4$s are HTML tags for a link.
			sprintf(
				'The following %1$s had multiple recurrence rules and were split into multiple recurring ' .
				'%1$s with identical content. Each recurring %2$s is part of a new Series of the same name. ' .
				'Ticket(s) have been converted to %3$sSeries Passes%4$s:',
				tribe_get_event_label_plural_lowercase(),
				tribe_get_event_label_singular_lowercase(),
				'<a href="https://evnt.is/sp-migration" target="_blank">',
				'</a>'
			),
			'The header message displayed after the migration of ticketed Recurring Events with multiple recurrence rules.',
			'event-tickets'
		);

		return $map;
	}

	/**
	 * Filters the CT1 Migration report headers to add headers for this plugin.
	 *
	 * @since 5.8.0
	 *
	 * @param array<int,array{key: string , label: string}> $report_headers
	 * @param String_Dictionary                             $text A reference to the String Dictionary used to
	 *                                                            localize the headers.
	 *
	 * @return array<int,array{key: string , label: string}> The altered headers.
	 */
	public function filter_report_headers( array $report_headers, $text ): array {
		$phase = $this->container->get( State::class )->get_phase();

		$positions = array_column( $report_headers, 'key' );
		// If neither is found, the result is false, which is cast to 0 hence the headers are prepended.
		$single_rule_pos = array_search( Single_Rule_Event_Migration_Strategy::get_slug(), $positions, true );
		$multi_rule_pos  = array_search( Multi_Rule_Event_Migration_Strategy::get_slug(), $positions, true );
		if ( $single_rule_pos === false && $multi_rule_pos === false ) {
			$insert_position = count( $report_headers ) - 1;
		} else {
			$insert_position = max( $single_rule_pos, $multi_rule_pos ) + 1;
		}

		$report_headers = array_merge(
			array_slice( $report_headers, 0, $insert_position ),
			[
				[
					'key'   => Ticketed_Single_Rule_Event_Migration_Strategy::get_slug(),
					'label' => $text->get( "$phase-strategy-" . Ticketed_Single_Rule_Event_Migration_Strategy::get_slug() ),
				],
				[
					'key'   => Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug(),
					'label' => $text->get( "$phase-strategy-" . Ticketed_Multi_Rule_Event_Migration_Strategy::get_slug() ),
				]
			],
			array_slice( $report_headers, $insert_position )
		);

		$report_headers[] = [
			'key'   => RSVP_Ticketed_Recurring_Event_Strategy::get_slug(),
			'label' => $text->get( 'migration-error-recurring-with-rsvp-tickets' ),
		];

		return $report_headers;
	}
}
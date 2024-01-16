<?php
/**
 * Provides common methods for the migration strategies dealing with Ticketed Recurring Events (excluding RSVP).
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\CT1_Migration\CT1_Migration_Checks;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Trait Ticketed_Recurring_Event_Strategy_Trait.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */
trait Ticketed_Recurring_Event_Strategy_Trait {
	use CT1_Migration_Checks;

	/**
	 * Ticketed Single and Multi recurrence rule constructor.
	 *
	 * since 5.8.0
	 *
	 * @param int  $post_id The post ID of the Event to migrate.
	 * @param bool $dry_run Whether the migration should actually commit information or not.
	 *
	 * @throws Migration_Exception If the post is not an Event or the Event is not Recurring, or the Event has no
	 *                             tickets.
	 */
	public function __construct( $post_id, $dry_run ) {
		parent::__construct( $post_id, $dry_run );

		if ( ! count( $this->get_ticket_ids( $post_id ) ) ) {
			throw new Migration_Exception( 'Recurring Event has no tickets.' );
		}

		if ( $dry_run ) {
			add_action( 'tec_events_custom_tables_v1_migration_after_dry_run', [ $this, 'clean_post_caches' ] );
		}
	}

	/**
	 * A list of post IDs that have been touched by this strategy.
	 *
	 * The property is used to ensure the cache is cleared for these posts after a dry run.
	 *
	 * @since 5.8.0
	 *
	 * @var array<int>
	 */
	private array $touched_post_ids = [];

	/**
	 * Returns a list of meta keys relating a Ticket or an Attende to the Event.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The Ticket or Attendee ID.
	 *
	 * @return string[] A list of meta keys relating a Ticket or an Attende to the Event.
	 */
	private function get_event_relationship_meta_keys( int $post_id ): array {
		$post_meta = get_post_meta( $post_id );

		$meta_keys = [];
		foreach ( array_keys( $post_meta ) as $meta_key ) {
			if ( preg_match( '/^_(tribe|tec)_.*_event$/', $meta_key ) ) {
				$meta_keys[] = $meta_key;
			}
		}

		return $meta_keys;
	}

	/**
	 * Ensures that the Series Post Type is ticketable.
	 *
	 * This is required because the Series Post Type is not ticketable by default and the migration cannot rely on
	 * the option being already set. This method is idem-potent and each migration instance running it will have
	 * the same effect.
	 *
	 * @since 5.8.0
	 */
	protected function ensure_series_ticketable(): void {
		$ticketable_post_types   = Tickets_Main::instance()->post_types();
		$ticketable_post_types[] = Series_Post_Type::POSTTYPE;
		$ticketable_post_types   = array_unique( $ticketable_post_types );
		tribe_update_option( 'ticket-enabled-post-types', $ticketable_post_types );
	}

	/**
	 * Returns the IDs of the Attendees for a given Ticket.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The Ticket ID.
	 *
	 * @return int[] The post IDs of the Attendees for the given Ticket.
	 */
	protected function get_attendee_ids( $ticket_id ): array {
		return tribe_attendees()->where( 'ticket', $ticket_id )->get_ids();
	}


	/**
	 * Moves the Tickets and their Attendees to the Series.
	 *
	 * In the process, each Ticket is converted to a Series Pass.
	 * The "move" of Tickets and Attendees is performed by updating the meta keys that relate them to the Event,
	 * a low-level operation that does not trigger any hooks.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The ID of the Series to move the Tickets to.
	 *
	 * @return array{0: int, 1: array<int,array<int>>} The list of moved Tickets and the list of moved Attendees,
	 *                                                 grouped by Ticket.
	 */
	protected function move_tickets_to_series( int $series_id ): array {
		$moved_tickets   = [];
		$moved_attendees = [];
		foreach ( $this->get_ticket_ids( $this->post_id ) as $ticket_id ) {
			$meta_keys = $this->get_event_relationship_meta_keys( $ticket_id );

			// Attach the Ticket to the Series.
			foreach ( $meta_keys as $meta_key ) {
				update_post_meta( $ticket_id, $meta_key, $series_id );
			}

			// Update the Ticket type to Series Pass.
			update_post_meta( $ticket_id, '_type', Series_Passes::TICKET_TYPE );

			$moved_tickets[]                = $ticket_id;
			$moved_attendees [ $ticket_id ] = [];

			$attendee_ids = $this->get_attendee_ids( $ticket_id );

			if ( ! count( $attendee_ids ) ) {
				continue;
			}

			// Use the first Attendee to sample the meta keys that relate Attendees to the Event.
			$first_attendee_id = $attendee_ids[0];
			$meta_keys         = $this->get_event_relationship_meta_keys( $first_attendee_id );

			foreach ( $attendee_ids as $attendee_id ) {
				// Attach the Attendee to the Series.
				foreach ( $meta_keys as $meta_key ) {
					update_post_meta( $attendee_id, $meta_key, $series_id );
					$moved_attendees[ $ticket_id ][] = $attendee_id;
				}
			}
		}

		return [ $moved_tickets, $moved_attendees ];
	}

	/**
	 * Sets the default ticket provider for the given Series by either using the one set for the Event or the default
	 * one.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The ID of the Series.
	 */
	protected function set_default_ticket_provider( int $series_id ): void {
		$meta_key        = tribe( 'tickets.handler' )->key_provider_field;
		$ticket_provider = get_post_meta( $this->post_id, $meta_key, true );
		if ( empty( $ticket_provider ) ) {
			$ticket_provider = Tickets::get_default_module();
		}
		update_post_meta( $series_id, $meta_key, str_replace( '\\', '\\\\', $ticket_provider ) );
	}

	/**
	 * Sets the meta related to global capacity for the given Series using the one set for the Event.
	 *
	 * This method is non-destructive by design: the Event meta will not be removed.
	 *
	 * @since 5.8.0
	 *
	 * @param int $series_id The ID of the Series.
	 */
	protected function set_global_capacity( int $series_id ): void {
		foreach (
			[
				tribe( 'tickets.handler' )->key_capacity,
				Global_Stock::GLOBAL_STOCK_ENABLED,
				Global_Stock::GLOBAL_STOCK_LEVEL,
			] as $meta_key
		) {
			$meta_value = get_post_meta( $this->post_id, $meta_key, true );

			if ( $meta_value === '' ) {
				// No need to set if not set on the event to begin with.
				return;
			}

			update_post_meta( $series_id, $meta_key, $meta_value );
		}
	}

	/**
	 * Applies the strategy to the given Event and updates the Event_Report.
	 *
	 * @since 5.8.0
	 *
	 * @param Event_Report $event_report The Event_Report to update.
	 *
	 * @throws Migration_Exception If the Series is not found.
	 */
	public function apply( Event_Report $event_report ): Event_Report {
		// Backup the Ticket Provider to restore it after the original migration strategy is applied: it might change it.
		$provider_meta_key = tribe( 'tickets.handler' )->key_provider_field;
		$ticket_provider   = get_post_meta( $this->post_id, $provider_meta_key, true );

		parent::apply( $event_report );

		// Restore the Ticket Provider handling namespaced class names (e.g. Commerce).
		$escaped_ticket_provider = str_replace( '\\', '\\\\', $ticket_provider );
		update_post_meta( $this->post_id, $provider_meta_key, $escaped_ticket_provider );

		if ( $event_report->status !== 'success' ) {
			return $event_report;
		}

		$strings = tribe( String_Dictionary::class );

		$series = tec_series()->where( 'event_post_id', $this->post_id )->first_id();

		if ( $series === null ) {
			throw new Migration_Exception( sprintf(
				$strings->get( 'migration-failure-series-not-found' ),
				$this->get_event_link_markup( $this->post_id )
			) );
		}

		$this->ensure_series_ticketable();

		[ $moved_tickets, $moved_attendees ] = $this->move_tickets_to_series( $series );

		// Store the touched Ticket and Attendees IDs to clean their cache after a dry run.
		$this->touched_post_ids = array_merge( [ $series ], $moved_tickets, ...$moved_attendees );

		$this->set_default_ticket_provider( $series );
		$this->set_global_capacity( $series );

		$event_report->set( 'moved_tickets', $moved_tickets );
		$moved_attendees_counts = array_combine(
			array_keys( $moved_attendees ),
			array_map( 'count', $moved_attendees )
		);
		$event_report->set( 'moved_attendees', $moved_attendees_counts );

		return $event_report;
	}

	/**
	 * Cleans the caches of the posts that have been touched by the migration dry-run.
	 *
	 * While the database status will be rolled back, persistent object cache that might have been
	 * altered cannot be rolled back; the next best thing is to clean the cache of the posts that
	 * have been touched by the migration.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The migrated Event post ID.
	 *
	 * @return void The object cache of the Series, Tickets and Attendees touched by the migration will be flushed.
	 */
	public function clean_post_caches( int $post_id ): void {
		if ( $post_id !== $this->post_id ) {
			return;
		}

		// Do not run this a second time, do not hold a reference to the migration strategy object.
		remove_action( 'tec_events_custom_tables_v1_migration_after_dry_run', [ $this, 'clean_post_caches' ] );

		foreach ( $this->touched_post_ids as $id ) {
			clean_post_cache( $id );
		}
	}
}
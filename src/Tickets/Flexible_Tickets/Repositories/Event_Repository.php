<?php
/**
 * Decorates the Events Tickets, or Event Tickets Plus Event Repository to include Series posts in the Tickets
 * or Attendees queries.
 *
 * Note: Event Tickets, or Event Tickets Plus if active, will both, in turn, decorate the Event Repository
 * provided by The Events Calendar or The Events Calendar Pro, if active.
 * This decorator will overwrite the schema entries added by the Event Tickets or Event Tickets Plus
 * decorators, so that the Tickets and Attendees queries include Series posts.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */

namespace TEC\Tickets\Flexible_Tickets\Repositories;

use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships;
use Tribe\Tickets\Repositories\Traits\Post_Attendees;
use Tribe\Tickets\Repositories\Traits\Post_Tickets;
use Tribe__Repository__Decorator as Repository_Decorator;
use Tribe__Repository__Interface as Repository_Interface;
use Tribe__Tickets__Event_Repository as Tickets_Event_Repository;
use Tribe__Tickets_Plus__Event_Repository as Tickets_Plus_Event_Repository;

/**
 * Class Event_Repository.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repositories;
 */
class Event_Repository extends Repository_Decorator {
	use Post_Tickets;
	use Post_Attendees;

	/**
	 * The meta keys that are used to store the Ticket to Event relationships.
	 *
	 * @since 5.8.0
	 *
	 * @var string[]
	 */
	private array $ticket_to_event_meta_keys;

	/**
	 * Event_Repository constructor.
	 *
	 * The decorator is constructed decorating either an Event Tickets or Event Tickets Plus Event Repository
	 * depending on which is available.
	 *
	 * @since 5.8.0
	 */
	public function __construct() {
		$this->decorated = $this->build_decorated_repository();

		// Overwrite the schema entries added by the ET/ET+ decorators to use this class methods.

		// These filter methods are added by the Post_Tickets trait.
		$this->decorated->add_schema_entry( 'cost', [ $this, 'filter_by_cost' ] );
		$this->decorated->add_schema_entry( 'cost_currency_symbol', [ $this, 'filter_by_cost_currency_symbol' ] );
		$this->decorated->add_schema_entry( 'has_tickets', [ $this, 'filter_by_has_tickets' ] );
		$this->decorated->add_schema_entry( 'has_rsvp', [ $this, 'filter_by_has_rsvp' ] );
		$this->decorated->add_schema_entry( 'has_rsvp_or_tickets', [ $this, 'filter_by_has_rsvp_or_tickets' ] );

		// These filter methods are added by the Post_Attendees trait.
		$this->decorated->add_schema_entry( 'has_attendees', [ $this, 'filter_by_has_attendees' ] );
		$this->decorated->add_schema_entry( 'attendee', [ $this, 'filter_by_attendee' ] );
		$this->decorated->add_schema_entry( 'attendee__not_in', [ $this, 'filter_by_attendee_not_in' ] );
		$this->decorated->add_schema_entry( 'attendee_user', [ $this, 'filter_by_attendee_user' ] );
	}

	/**
	 * Builds the decorated repository.
	 *
	 * @since 5.8.0
	 *
	 * @return Repository_Interface The decorated repository instance.
	 */
	private function build_decorated_repository(): Repository_Interface {
		if ( class_exists( Tickets_Plus_Event_Repository::class ) ) {
			return new Tickets_Plus_Event_Repository();
		}

		return new Tickets_Event_Repository();
	}

	/**
	 * Returns the SQL to include Series in the meta value comparison.
	 *
	 * @since 5.16.0
	 *
	 * @param string $alias The post meta table alias used in the context SQL.
	 *
	 * @return string The SQL to include Series in the meta value comparison.
	 */
	private function include_series_in_meta_value_compare( string $alias ): string {
		global $wpdb;

		$series_relationships = Series_Relationships::table_name();

		// Include Events that are either directly related to a Ticket (Single and Series Tickets) or
		// are related to a Series that is related to a Ticket (Series Passes).
		return "
			{$alias}.meta_value = {$wpdb->posts}.ID
			OR
			{$alias}.meta_value IN (
				SELECT sr.series_post_id FROM $series_relationships sr WHERE sr.event_post_id = {$wpdb->posts}.ID
			)";
	}

	/**
	 * This method is originally provided by the Post_Tickets trait; it's overridden here to
	 * include Series posts in the relationship lookups.
	 *
	 * @since 5.8.0
	 *
	 * @param string $alias The alias of the `postmeta` table to join.
	 *
	 * @return string The SQL clause to add to the query to compare the post IDs pointed by the meta key connecting
	 *                Tickets to Events.
	 */
	protected function ticket_to_post_meta_value_compare( string $alias ): string {
		return $this->include_series_in_meta_value_compare( $alias );
	}

	/**
	 * Builds the SQL clause to compare meta values to the ones relating attendees to posts.
	 *
	 * @since 5.8.0
	 *
	 * @param string $alias The alias to use for the post meta table.
	 *
	 * @return string The SQL clause to compare meta values to the ones relating tickets to posts.
	 */
	protected function attendee_to_post_meta_value_compare( string $alias ): string {
		return $this->include_series_in_meta_value_compare( $alias );
	}

	/**
	 * Return the meta keys that are used to store the Attendee to Event relationships.
	 *
	 * @since 5.8.0
	 *
	 * @return string[] The meta keys that are used to store the Ticket to Event relationships.
	 */
	public function attendee_to_event_keys(): array {
		return $this->decorated->attendee_to_event_keys();
	}

	/**
	 * Returns the Attendee post types.
	 *
	 * @since 5.8.0
	 *
	 * @return string[] The Attendee post types.
	 */
	public function attendee_types(): array {
		return $this->decorated->attendee_types();
	}

	/**
	 * Returns the meta key relating an Attendee to a User.
	 *
	 * @since 5.8.0
	 *
	 * @return string The meta key relating an Attendee to a User.
	 */
	public function attendee_to_user_key(): string {
		return $this->decorated->attendee_to_user_key();
	}
}

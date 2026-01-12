<?php
/**
 * V2 Ticket Repository for TC-RSVP tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Repositories\Traits\Get_Field;
use TEC\Tickets\RSVP\V2\Constants;
use Tribe__Tickets__Ticket_Repository as Base_Repository;

/**
 * Class Ticket_Repository
 *
 * Repository for querying TC-RSVP tickets.
 * Extends the base repository and automatically filters to only return tickets
 * with ticket_type = 'tc-rsvp'.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Ticket_Repository extends Base_Repository {
	use Get_Field;

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_rsvp_tickets';

	/**
	 * Override the default query args to only return TC-RSVP tickets.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();

		// Always filter by the TC-RSVP ticket type; replace the existing meta query, if any.
		$this->query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] = [
			'key'     => '_type',
			'compare' => '=',
			'value'   => Constants::TC_RSVP_TYPE,
		];
	}

	/**
	 * Override the ticket types to return TC-RSVP tickets only.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of ticket types supported by this repository.
	 */
	public function ticket_types() {
		return [ Commerce::PROVIDER => Ticket::POSTTYPE ];
	}

	/**
	 * Overrides the base repository method to return the Tickets Commerce Ticket to Event relation meta key.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The array of Ticket to Event relation meta keys supported by this repository.
	 */
	public function ticket_to_event_keys() {
		return [ Commerce::PROVIDER => Ticket::$event_relation_meta_key ];
	}

	/**
	 * Get the event ID for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return int|false Event ID or false if not found.
	 */
	public function get_event_id( int $ticket_id ) {
		$event_id = get_post_meta( $ticket_id, Ticket::$event_relation_meta_key, true );

		return $event_id ? (int) $event_id : false;
	}
}

<?php
/**
 * Repository Filters delegate for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Tribe__Repository__Interface as Repository_Interface;

/**
 * Class Repository_Filters
 *
 * Handles repository query filtering for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Repository_Filters {
	/**
	 * Filters the Tickets Commerce repository query args to exclude RSVP tickets from the list.
	 *
	 * @since TBD
	 *
	 * @param Repository_Interface $repository The repository instance, unused.
	 * @param array<string,mixed>  $query_args The query args to be used to fetch the tickets.
	 *
	 * @return array<string,mixed> The modified query args.
	 */
	public function exclude_rsvp_tickets_from_repository_queries( Repository_Interface $repository, array $query_args ): array {
		$query_args['meta_query'] = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ?
			$query_args['meta_query']
			: [];
		$context                  = $repository->get_request_context();

		// Let's make sure the meta query is not being added twice.
		foreach ( $query_args['meta_query'] as $meta_query ) {
			if (
				isset( $meta_query['key'], $meta_query['value'] )
				&& $meta_query['key'] === '_type'
				&& $meta_query['value'] === Constants::TC_RSVP_TYPE
			) {
				// The meta query has already been filtered to either exclude or include RSVP tickets, bail.
				return $query_args;
			}
		}

		if ( $context === 'front_end_tickets_form' ) {
			// Include RSVP tickets from the list.
			return $query_args;
		}

		// Exclude RSVP tickets from the list.
		$query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] = [
			'key'     => '_type',
			'compare' => '!=',
			'value'   => Constants::TC_RSVP_TYPE,
		];

		return $query_args;
	}

	/**
	 * Marks RSVP tickets as proper tickets in the ticket detection logic in Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param bool                $is_ticket Whether the thing is a ticket.
	 * @param array<string,mixed> $thing     The thing to check.
	 *
	 * @return bool Whether the thing is a ticket.
	 */
	public function rsvp_are_tickets( bool $is_ticket, array $thing ): bool {
		return isset( $thing['type'] ) && $thing['type'] === Constants::TC_RSVP_TYPE ? true : $is_ticket;
	}

	/**
	 * Filter the arguments used to fetch Tickets Commerce tickets to remove the RSVP tickets
	 * default exclusion if the request is for a specific ticket by ID.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $query_args The arguments used to fetch tickets.
	 *
	 * @return array<string,mixed> The modified arguments.
	 */
	public function include_rsvp_tickets_by_id( array $query_args ): array {
		if ( isset( $query_args['p'] ) ) {
			unset( $query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] );
		}

		return $query_args;
	}
}

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
	 * @param array<string,mixed>  $query_args The query args to be used to fetch the tickets.
	 * @param Repository_Interface $repository The repository instance, unused.
	 *
	 * @return array<string,mixed> The modified query args.
	 */
	public function exclude_rsvp_tickets_from_repository_queries( array $query_args, Repository_Interface $repository ): array {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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
	 * default exclusion if the request is for a specific ticket by ID or for the RSVP type.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $query_args The arguments used to fetch tickets.
	 *
	 * @return array<string,mixed> The modified arguments.
	 */
	public function maybe_include_rsvp_tickets( array $query_args ): array {
		if ( isset( $query_args['p'] ) ) {
			// The query is for a specific ticket ID, include RSVP.
			unset( $query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] );

			return $query_args;
		}

		if ( isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
			// The query is for the RSVP ticket type: include it.
			foreach ( $query_args['meta_query'] as $key => $meta_query ) {
				if ( Constants::TYPE_META_QUERY_KEY === $key ) {
					continue;
				}

				if (
					isset( $meta_query['key'], $meta_query['value'] )
					&& '_type' === $meta_query['key']
					&& Constants::TC_RSVP_TYPE === $meta_query['value']
				) {
					unset( $query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] );

					return $query_args;
				}
			}
		}

		return $query_args;
	}
}

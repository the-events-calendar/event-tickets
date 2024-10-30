<?php
/**
 * Post tickets trait that contains all of the ORM filters that can be used for any repository.
 *
 * @since   4.12.1
 *
 * @package Tribe\Tickets\Repositories\Traits
 */

namespace Tribe\Tickets\Repositories\Traits;

use Tribe__Repository as Repository;
use Tribe__Repository__Usage_Error as Usage_Error;
use Tribe__Utils__Array as Arr;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Repository__Void_Query_Exception as Void_Query_Exception;
use TEC\Tickets\Commerce\Module as Tickets_Commerce;

/**
 * Class Post_Tickets
 *
 * @since 4.12.1
 */
trait Post_Tickets {

	/**
	 * A re-implementation of the base `filter_by_cost` method to filter events by related
	 * ticket costs in place of their own cost meta.
	 *
	 * @since 4.12.1
	 *
	 * @param float|array $value       The cost to use for the comparison; in the case of `BETWEEN`, `NOT BETWEEN`,
	 *                                 `IN` and `NOT IN` operators this value should be an array.
	 * @param string      $operator    Teh comparison operator to use for the comparison, one of `<`, `<=`, `>`, `>=`,
	 *                                 `=`, `BETWEEN`, `NOT BETWEEN`, `IN`, `NOT IN`.
	 * @param string      $symbol      The desired currency symbol or symbols; this symbol can be a currency ISO code,
	 *                                 e.g. "USD" for U.S. dollars, or a currency symbol, e.g. "$".
	 *                                 In the latter case results will include any event with the matching currency
	 *                                 symbol, this might lead to ambiguous results.
	 *
	 * @throws Usage_Error If the comparison operator is not supported of is using the `BETWEEN`,
	 *                                        `NOT BETWEEN` operators without passing a two element array `$value`.
	 */
	public function filter_by_cost( $value, $operator = '=', $symbol = null ) {
		$repo = $this;

		// If the repo is decorated, use that.
		if ( ! empty( $repo ) ) {
			$repo = $this->decorated;
		}

		$operators = [
			'<',
			'<=',
			'>',
			'>=',
			'=',
			'!=',
			'BETWEEN',
			'NOT BETWEEN',
			'IN',
			'NOT IN',
		];
		if ( ! in_array( $operator, $operators, true ) ) {
			throw Usage_Error::because_this_comparison_operator_is_not_supported( $operator, 'filter_by_cost' );
		}

		if (
			in_array( $operator, [ 'BETWEEN', 'NOT BETWEEN' ], true )
			&& ! (
				is_array( $value )
				&& 2 === count( $value )
			)
		) {
			throw Usage_Error::because_this_comparison_operator_requires_an_value_of_type( $operator, 'filter_by_cost', 'array' );
		}

		if ( in_array( $operator, [ 'IN', 'NOT IN' ], true ) ) {
			$value = (array) $value;
		}

		$operator_name = Arr::get( Repository::get_comparison_operators(), $operator, '' );
		$prefix        = str_replace( '-', '_', 'by_cost_' . $operator_name );

		global $wpdb;

		$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event" );
		$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );

		// Join to the meta that relates tickets to events.
		$repo->join_clause( "JOIN {$wpdb->postmeta} {$prefix}_ticket_event
			ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );

		// Join to the ticket cost meta, allow for RSVP tickets too: they have no price.
		$repo->join_clause( "LEFT JOIN {$wpdb->postmeta} {$prefix}_ticket_cost
			ON (
					{$prefix}_ticket_cost.post_id = {$prefix}_ticket_event.post_id
					AND (
						({$prefix}_ticket_cost.meta_key = '_price' OR {$prefix}_ticket_cost.meta_key = 'edd_price')
						OR {$prefix}_ticket_cost.meta_id IS NULL
					)
			)" );

		if($operator !== 'BETWEEN' && $operator !== 'NOT BETWEEN'){
			$prepared_value = is_array( $value ) ?
				$repo->prepare_interval( $value, '%d', $operator )
				: $wpdb->prepare( '%d', $value );
		} else {
			if ( ! ( is_array( $value ) && count( $value ) === 2 ) ) {
				throw Usage_Error::because_this_comparison_operator_requires_an_value_of_type( $operator, 'filter_by_cost', 'array of two numbers' );
			}
			// (NOT )BETWEEN requires two values in the shape `(NOT )BETWEEN) %d AND %d` for full SQL compatibility.
			$prepared_value = implode( ' AND ', (array) $value );
		}

		// Default the cost to `0` if not set to make RSVP tickets show as "free" tickets, with a cost of 0.
		$repo->where_clause( "IFNULL( {$prefix}_ticket_cost.meta_value, 0 ) {$operator} {$prepared_value}" );

		if ( null !== $symbol ) {
			$this->filter_by_cost_currency_symbol( $symbol );
		}
	}

	/**
	 * Filters events that have a ticket with a specific cost currency symbol.
	 *
	 * Events with a cost of `0` but a currency symbol set will be fetched when fetching
	 * by their symbols; RSVP tickets have no symbol and will never match any filtering
	 * by currency symbol.
	 * Filtering by currency symbol, when done in the context of Event Tickets, really means
	 * filtering events by tickets that come from providers with a specific currency ISO code.
	 * As an example filtering by "USD" when Tribe Commerce tickets use the "EUR" code and
	 * WooCommerce tickets use the "USD" code means "only fetch events that have WooCommerce
	 * tickets".
	 *
	 * @since 4.12.1
	 *
	 * @param string|array $symbol One or more currency symbols or currency ISO codes. E.g.
	 *                             "$" and "USD".
	 *
	 * @throws Void_Query_Exception If no provider uses the specified currency symbol
	 *                                                 or ISO code.
	 */
	public function filter_by_cost_currency_symbol( $symbol ) {
		$repo = $this;

		// If the repo is decorated, use that.
		if ( ! empty( $repo ) ) {
			$repo = $this->decorated;
		}

		/** @var \Tribe__Tickets__Commerce__Currency $currency */
		$currency      = tribe( 'tickets.commerce.currency' );
		$symbols       = (array) $symbol;
		$request_codes = [];

		/*
		 * Transform the request symbols into ISO codes; due to its ambiguous nature a
		 * symbol might match 0+ ISO codes.
		 */
		foreach ( $symbols as $request_symbol ) {
			$request_codes[] = (array) $currency->get_symbol_codes( $request_symbol );
		}
		$request_codes = array_unique( array_merge( ...$request_codes ) );

		if ( empty( $request_codes ) ) {
			$reason = 'The specified currency symbol or ISO code is not supported.';
			throw Void_Query_Exception::because_the_query_would_yield_no_results( $reason );
		}

		// Compile a list of ticket providers that are active and use one of the requested ISO codes.
		$request_providers = [];
		foreach ( Tickets::modules() as $provider => $name ) {
			if ( $provider === RSVP::class ) {
				// RSVP tickets have no cost, so they have no currency symbol.
				continue;
			}

			if ( $provider === PayPal::class || $provider === Tickets_Commerce::class ) {
				// Built-in providers do not require symbol to code mapping: just the ISO code is enough.
				if ( array_intersect( $request_codes, (array) $currency->get_currency_code() ) ) {
					$request_providers[] = $provider;
				}
				continue;
			}

			$provider_symbol = $currency->get_provider_symbol( $provider );

			if ( ! is_string( $provider_symbol ) ) {
				continue;
			}

			$provider_codes = (array) $currency->get_symbol_codes( $provider_symbol );

			if ( array_intersect( $request_codes, $provider_codes ) ) {
				// This provider uses one of the request ISO codes, use it.
				$request_providers[] = $provider;
			}
		}

		if ( empty( $request_providers ) ) {
			$reason = 'No ticket provider uses the specified currency symbol or ISO code.';
			throw Void_Query_Exception::because_the_query_would_yield_no_results( $reason );
		}

		global $wpdb;
		$prefix = 'by_cost_currency_symbol';

		$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event", $request_providers );
		$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );

		// Join to the meta that relates tickets to events but only for the providers that have the required symbols.
		$repo->join_clause( "JOIN {$wpdb->postmeta} {$prefix}_ticket_event
			ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );
	}

	/**
	 * Filters events to include only those that match the provided ticket state.
	 *
	 * This does NOT include RSVPs or events that have a cost assigned via the
	 * cost custom field.
	 *
	 * @since 4.12.1
	 * @since 5.8.3 Refactored to catch instances when `$meta_key_compare_clause` is empty.
	 *
	 * @param bool $has_tickets Indicates if the event should have ticket types attached to it or not.
	 */
	public function filter_by_has_tickets( $has_tickets = true ) {
		global $wpdb;
		$prefix = $has_tickets ? 'has_tickets' : 'has_no_tickets';

		$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event", null, $has_tickets ? [ RSVP::class ] : null );
		$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );

		// Start with the JOIN type based on the ticket presence.
		$join_clause = ( $has_tickets ? 'JOIN' : 'LEFT JOIN' ) . " {$wpdb->postmeta} {$prefix}_ticket_event ON ";

		// Add conditions if they are not empty, with an AND if both are present.
		if ( ! empty( $meta_key_compare_clause ) ) {
			$join_clause .= "($meta_key_compare_clause)";
		}
		if ( ! empty( $meta_key_compare_clause ) && ! empty( $meta_value_compare_clause ) ) {
			$join_clause .= ' AND ';
		}
		if ( ! empty( $meta_value_compare_clause ) ) {
			$join_clause .= "($meta_value_compare_clause)";
		}

		$this->join_clause( $join_clause );

		// Additional logic for when tickets are not expected.
		if ( ! $has_tickets ) {
			$this->where_clause( "{$prefix}_ticket_event.meta_key = '_tribe_rsvp_for_event' OR {$prefix}_ticket_event.meta_id IS NULL" );
		}
	}


	/**
	 * Filters events to include only those that match the provided RSVP state.
	 *
	 * @since 4.12.1
	 *
	 * @param bool $has_rsvp Indicates if the event should have RSVP tickets attached to it or not.
	 */
	public function filter_by_has_rsvp( $has_rsvp = true ) {
		$repo = $this;

		// If the repo is decorated, use that.
		if ( ! empty( $repo ) ) {
			$repo = $this->decorated;
		}

		global $wpdb;
		$prefix = 'has_rsvp_';

		if ( (bool) $has_rsvp ) {
			$prefix                    = 'has_rsvp';
			$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event", [ RSVP::class ] );
			$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );

			$repo->join_clause( "JOIN {$wpdb->postmeta} {$prefix}_ticket_event
				 ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );

			return;
		}

		$prefix                    = 'has_no_rsvp';
		$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event", [ RSVP::class ] );
		$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );
		// Keep events that have no RSVPs assigned.
		$repo->join_clause( "LEFT JOIN {$wpdb->postmeta} {$prefix}_ticket_event
					ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );
		$repo->where_clause( "{$prefix}_ticket_event.meta_id IS NULL" );
	}

	/**
	 * Filters events to include only those that match the provided RSVP or Ticket state.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $has_rsvp_or_tickets Indicates if the event should have RSVP or tickets attached to it or not.
	 */
	public function filter_by_has_rsvp_or_tickets( $has_rsvp_or_tickets = true ) {
		$repo    = $this;

		// If the repo is decorated, use that.
		if ( ! empty( $repo ) ) {
			$repo = $this->decorated;
		}

		global $wpdb;

		if ( (bool) $has_rsvp_or_tickets ) {
			$prefix                    = 'has_rsvp_or_tickets';
			$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event" );
			$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );

			// Join to the meta that relates tickets to events but exclude RSVP tickets.
			$repo->join_clause( "JOIN {$wpdb->postmeta} {$prefix}_ticket_event
				 ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );

			return;
		}

		$prefix                    = 'has_no_rsvp_or_tickets';
		$meta_key_compare_clause   = $this->ticket_to_post_meta_key_compare( "{$prefix}_ticket_event" );
		$meta_value_compare_clause = $this->ticket_to_post_meta_value_compare( "{$prefix}_ticket_event" );
		// Keep events that have no tickets or RSVP assigned.
		$repo->join_clause( "LEFT JOIN {$wpdb->postmeta} {$prefix}_ticket_event
					ON ({$meta_key_compare_clause}) AND ({$meta_value_compare_clause})" );
		$repo->where_clause( "{$prefix}_ticket_event.meta_id IS NULL" );
	}

	/**
	 * Builds the SQL clause to compare meta keys to the ones relating tickets to posts.
	 *
	 * The clause is built with ORs to allow for multiple meta keys to be used and for the
	 * meta key index to kick in.
	 *
	 * @since 5.8.0
	 * @since 5.8.3 Set $meta_keys to an empty array.
	 * @since 5.8.4 Implemented a check for when $meta_keys is empty to not prepare anything for the query.
	 *
	 * @param string   $alias     The alias to use for the post meta table.
	 * @param string[] $allow     A list of providers to include in the comparison. If this argument is `null`,
	 *                            all active providers will be included.
	 * @param string[] $exclude   A list of providers to exclude from the comparison. If this argument is `null`,
	 *                            no providers will be excluded.
	 *
	 * @return string The SQL clause to compare meta keys to the ones relating tickets to posts.
	 */
	protected function ticket_to_post_meta_key_compare( string $alias, array $allow = null, array $exclude = null ): string {
		$meta_keys = [];
		foreach ( Tickets::modules() as $provider => $name ) {
			if ( $allow !== null && ! in_array( $provider, $allow, true ) ) {
				continue;
			}

			if ( $exclude !== null && in_array( $provider, $exclude, true ) ) {
				continue;
			}

			$meta_keys[] = tribe( $provider )->get_event_key();
		}

		if ( empty( $meta_keys ) ) {
			return '';
		}

		$unprepared = implode( " OR ", array_fill( 0, count( $meta_keys ), "$alias.meta_key = %s" ) );

		global $wpdb;

		return $wpdb->prepare( $unprepared, ...$meta_keys );
	}

	/**
	 * Builds the SQL clause to compare meta values to the ones relating tickets to posts.
	 *
	 * @since 5.8.0
	 *
	 * @param string $alias      The alias to use for the post meta table.
	 *
	 * @return string The SQL clause to compare meta values to the ones relating tickets to posts.
	 */
	protected function ticket_to_post_meta_value_compare( string $alias ): string {
		global $wpdb;

		return "{$alias}.meta_value = {$wpdb->posts}.ID";
	}
}

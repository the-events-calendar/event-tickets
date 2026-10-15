<?php
/**
 * Reads and writes the rules stored on a ticket.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;


/**
 * Keeps the ticket's rules in one JSON meta, writing only the keys it is given so one rule never drops another.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rule_Store {
	/**
	 * The ticket meta that holds the rules, as a JSON object.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const META_KEY = '_tec_tickets_relative_sale_dates';

	/**
	 * The most ruled tickets the query for one event returns, so an event with thousands of tickets cannot run it unbounded.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const TICKETS_QUERY_LIMIT = 300;

	/**
	 * How many times a write is tried against fresh meta when another request changed the rules since they were read.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const WRITE_ATTEMPTS = 3;

	/**
	 * Gets everything stored for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start?: array<string,mixed>, end?: array<string,mixed>, sale_price?: array{start?: array<string,mixed>, end?: array<string,mixed>}} The stored rules, keyed by their top-level key, or an empty array.
	 */
	public function get( int $ticket_id ): array {
		return $this->decode( $this->read( $ticket_id ) );
	}

	/**
	 * Gets the Tickets Commerce tickets of an event that have stored rules, at most `TICKETS_QUERY_LIMIT` of them.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event post ID.
	 *
	 * @return int[] The ticket post IDs, none while Tickets Commerce is off.
	 */
	public function get_ticket_ids_for_event( int $event_id ): array {
		// Tickets Commerce loads its ticket functions only while it is on, and events are saved whether it is or not.
		if ( ! tec_tickets_commerce_is_enabled() || ! function_exists( 'tec_tc_tickets' ) ) {
			return [];
		}

		$ticket_ids = array_map(
			'absint',
			tec_tc_tickets()
				->where( 'event', $event_id )
				->where( 'meta_exists', self::META_KEY )
				->where( 'post_status', 'any' )
				->order_by( 'ID', 'ASC' )
				->per_page( self::TICKETS_QUERY_LIMIT )
				->get_ids()
		);

		// A full page may leave tickets out; the oldest ones are kept, and the cut is logged so it can be found.
		if ( count( $ticket_ids ) >= self::TICKETS_QUERY_LIMIT ) {
			do_action(
				'tribe_log',
				'warning',
				__CLASS__,
				[
					'message'  => 'Only the first ruled tickets of the event were read; the others keep their dates.',
					'event_id' => $event_id,
					'limit'    => self::TICKETS_QUERY_LIMIT,
				]
			);
		}

		return $ticket_ids;
	}

	/**
	 * Writes the given top-level keys, leaving the other stored keys as they are.
	 *
	 * @since TBD
	 *
	 * @param int                                                                                             $ticket_id The ticket post ID.
	 * @param array{start?: array<string,mixed>, end?: array<string,mixed>, sale_price?: array<string,mixed>} $values    The values to write, keyed by their top-level key.
	 *
	 * @return void
	 */
	public function save( int $ticket_id, array $values ): void {
		$this->change( $ticket_id, static fn( array $stored ): array => array_merge( $stored, $values ) );
	}

	/**
	 * Removes the given top-level keys, and the meta once nothing is left in it.
	 *
	 * @since TBD
	 *
	 * @param int      $ticket_id The ticket post ID.
	 * @param string[] $keys      The top-level keys to remove.
	 *
	 * @return bool Whether any of the keys was stored.
	 */
	public function remove( int $ticket_id, array $keys ): bool {
		$removed = false;

		$this->change(
			$ticket_id,
			static function ( array $stored ) use ( $keys, &$removed ): ?array {
				$removed = (bool) array_intersect_key( $stored, array_flip( $keys ) );

				return $removed ? array_diff_key( $stored, array_flip( $keys ) ) : null;
			}
		);

		return $removed;
	}

	/**
	 * Saves a rule of any kind under the keys its kind stores it in, leaving the rules of the other kinds as they are.
	 *
	 * @since TBD
	 *
	 * @param int  $ticket_id The ticket post ID.
	 * @param Rule $rule      The rule.
	 *
	 * @return void
	 */
	public function save_rule( int $ticket_id, Rule $rule ): void {
		$key = $rule->get_kind()->get_store_key();

		$this->save( $ticket_id, null === $key ? $rule->to_array() : [ $key => $rule->to_array() ] );
	}

	/**
	 * Removes the rule of a kind, leaving the rules of the other kinds as they are.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the rule to remove.
	 *
	 * @return bool Whether a rule of the kind was stored.
	 */
	public function remove_rule( int $ticket_id, Window_Kind $kind ): bool {
		$key = $kind->get_store_key();

		return $this->remove( $ticket_id, null === $key ? [ 'start', 'end' ] : [ $key ] );
	}

	/**
	 * Changes the stored rules, writing them only if no other request changed them since they were read.
	 *
	 * Two saves of one ticket can overlap, and a write of what one read would drop the other's rule or bring back one it
	 * removed. On such a conflict the rules are read again, from the database, and changed again.
	 *
	 * @since TBD
	 *
	 * @param int                                                      $ticket_id The ticket post ID.
	 * @param callable(array<string,mixed>):(array<string,mixed>|null) $change    Returns the rules to write from the stored
	 *                                                                            ones, or `null` to write nothing.
	 *
	 * @return void
	 */
	private function change( int $ticket_id, callable $change ): void {
		for ( $attempt = 1; $attempt <= self::WRITE_ATTEMPTS; $attempt++ ) {
			$read = $this->read( $ticket_id );
			$data = $change( $this->decode( $read ) );

			if ( null === $data || $this->write( $ticket_id, $read, $data ) ) {
				return;
			}

			wp_cache_delete( $ticket_id, 'post_meta' );
		}

		do_action(
			'tribe_log',
			'warning',
			__CLASS__,
			[
				'message'   => 'The ticket rules kept changing while they were written; the last write was dropped.',
				'ticket_id' => $ticket_id,
			]
		);
	}

	/**
	 * Writes the rules, or deletes the meta when there are none, if the meta still holds what was read.
	 *
	 * @since TBD
	 *
	 * @param int                                                                                             $ticket_id The ticket post ID.
	 * @param string                                                                                          $read      The meta as it was read, `''` for none.
	 * @param array{start?: array<string,mixed>, end?: array<string,mixed>, sale_price?: array<string,mixed>} $data      The rules to write, keyed by their top-level key.
	 *
	 * @return bool Whether the meta held what was read, and now holds the rules.
	 */
	private function write( int $ticket_id, string $read, array $data ): bool {
		if ( ! $data ) {
			// The meta functions unslash the value they compare against.
			return '' === $read || delete_post_meta( $ticket_id, self::META_KEY, wp_slash( $read ) );
		}

		$json = wp_json_encode( $data );

		if ( ! is_string( $json ) || $json === $read ) {
			return true;
		}

		if ( '' === $read ) {
			return false !== add_post_meta( $ticket_id, self::META_KEY, wp_slash( $json ), true );
		}

		/*
		 * Unlike the new value, the previous value is compared as it is given.
		 * shortcut: `update_post_meta()` adds the meta when another request deleted it after the read, which brings that
		 * request's removed rules back; the window is the time between two queries of overlapping saves of one ticket.
		 */
		return false !== update_post_meta( $ticket_id, self::META_KEY, wp_slash( $json ), $read );
	}

	/**
	 * Reads the stored meta.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return string The stored JSON, or `''` when nothing is stored.
	 */
	private function read( int $ticket_id ): string {
		$json = get_post_meta( $ticket_id, self::META_KEY, true );

		return is_string( $json ) ? $json : '';
	}

	/**
	 * Decodes the stored meta.
	 *
	 * @since TBD
	 *
	 * @param string $json The stored JSON.
	 *
	 * @return array<string,mixed> The stored rules, keyed by their top-level key, or an empty array.
	 */
	private function decode( string $json ): array {
		$data = '' === $json ? null : json_decode( $json, true );

		return is_array( $data ) ? $data : [];
	}
}

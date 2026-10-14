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
	 * Gets everything stored for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start?: array<string,mixed>, end?: array<string,mixed>, sale_price?: array{start?: array<string,mixed>, end?: array<string,mixed>}} The stored rules, keyed by their top-level key, or an empty array.
	 */
	public function get( int $ticket_id ): array {
		$json = get_post_meta( $ticket_id, self::META_KEY, true );

		if ( ! is_string( $json ) || '' === $json ) {
			return [];
		}

		$data = json_decode( $json, true );

		return is_array( $data ) ? $data : [];
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
	 * @param int                                                                                                               $ticket_id The ticket post ID.
	 * @param array{start?: Boundary|array<string,mixed>, end?: Boundary|array<string,mixed>, sale_price?: array<string,mixed>} $values    The values to write, keyed by their top-level key.
	 *
	 * @return void
	 */
	public function save( int $ticket_id, array $values ): void {
		$this->write( $ticket_id, array_merge( $this->get( $ticket_id ), $values ) );
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
		$stored = $this->get( $ticket_id );

		if ( ! array_intersect_key( $stored, array_flip( $keys ) ) ) {
			return false;
		}

		$this->write( $ticket_id, array_diff_key( $stored, array_flip( $keys ) ) );

		return true;
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
	 * Writes the rules, or deletes the meta when there are none.
	 *
	 * @since TBD
	 *
	 * @param int                                                                                                               $ticket_id The ticket post ID.
	 * @param array{start?: Boundary|array<string,mixed>, end?: Boundary|array<string,mixed>, sale_price?: array<string,mixed>} $data    The rules to write, keyed by their top-level key.
	 *
	 * @return void
	 */
	private function write( int $ticket_id, array $data ): void {
		if ( ! $data ) {
			delete_post_meta( $ticket_id, self::META_KEY );

			return;
		}

		$json = wp_json_encode( $data );

		if ( ! is_string( $json ) ) {
			return;
		}

		update_post_meta( $ticket_id, self::META_KEY, wp_slash( $json ) );
	}
}

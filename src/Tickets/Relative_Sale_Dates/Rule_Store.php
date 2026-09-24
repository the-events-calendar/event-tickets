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
	 * Gets everything stored for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array<string,mixed> The stored rules, keyed by their top-level key, or an empty array.
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
	 * Writes the given top-level keys, leaving the other stored keys as they are.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID.
	 * @param array<string,mixed> $values    The values to write, keyed by their top-level key.
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
	 * @return void
	 */
	public function remove( int $ticket_id, array $keys ): void {
		$stored = $this->get( $ticket_id );

		if ( ! array_intersect_key( $stored, array_flip( $keys ) ) ) {
			return;
		}

		$this->write( $ticket_id, array_diff_key( $stored, array_flip( $keys ) ) );
	}

	/**
	 * Writes the rules, or deletes the meta when there are none.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID.
	 * @param array<string,mixed> $data      The rules to write, keyed by their top-level key.
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

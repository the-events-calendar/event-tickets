<?php
/**
 * Parses the raw `tec_tickets` value of a request into a payload.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */

namespace TEC\Tickets\Deferred_Save\Payload;

use TEC\Tickets\Deferred_Save\Payload;

/**
 * Class Parser.
 *
 * Normalizes the raw array and keeps the entries that match the contract; every entry that does
 * not is dropped and recorded in the rejections. A value that is not an array, or that carries
 * an unknown part, is rejected as a whole and yields an empty payload.
 *
 * The parser makes no WordPress calls beyond translation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
class Parser {
	/**
	 * Every part the contract knows.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const PARTS = [ Payload::UPDATE, Payload::CREATE, Payload::DELETE, Payload::MOVE ];

	/**
	 * Parses the raw `tec_tickets` value of a request.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw value. `null` or an empty array means "no ticket changes".
	 * @param Rejections $rejections Where the entries that do not match the contract are recorded.
	 *
	 * @return Payload The accepted entries.
	 */
	public function parse( $raw, Rejections $rejections ): Payload {
		if ( null === $raw || [] === $raw ) {
			return new Payload();
		}

		if ( ! is_array( $raw ) ) {
			$rejections->add( null, null, __( 'The ticket changes must be an array.', 'event-tickets' ) );

			return new Payload();
		}

		foreach ( array_keys( $raw ) as $part ) {
			if ( ! in_array( $part, self::PARTS, true ) ) {
				$rejections->add(
					null,
					(string) $part,
					sprintf(
						/* translators: %s: the unknown key. */
						__( 'Unknown ticket changes part "%s".', 'event-tickets' ),
						$part
					)
				);

				return new Payload();
			}
		}

		$update = $this->parse_update( $raw[ Payload::UPDATE ] ?? [], $rejections );
		$create = $this->parse_create( $raw[ Payload::CREATE ] ?? [], $rejections );
		$delete = $this->parse_delete( $raw[ Payload::DELETE ] ?? [], $rejections );
		$move   = $this->parse_move( $raw[ Payload::MOVE ] ?? [], $rejections );

		// The same ticket cannot be both updated and deleted; neither can be meant, so both go.
		foreach ( array_intersect( array_keys( $update ), $delete ) as $ticket_id ) {
			unset( $update[ $ticket_id ] );
			$delete = array_values( array_diff( $delete, [ $ticket_id ] ) );
			$rejections->add( Payload::UPDATE, $ticket_id, __( 'The same ticket cannot be both updated and deleted.', 'event-tickets' ) );
		}

		return new Payload( $update, $create, $delete, $move );
	}

	/**
	 * Parses the `update` part.
	 *
	 * The entry key is the ticket ID the checks verify, and the ticket save reads the ticket to
	 * write from `data['ticket_id']`, so the key overwrites whatever the data carried.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections Where rejected entries are recorded.
	 *
	 * @return array<int,array<string,mixed>> Ticket ID => data.
	 */
	private function parse_update( $raw, Rejections $rejections ): array {
		if ( ! $this->part_is_array( Payload::UPDATE, $raw, $rejections ) ) {
			return [];
		}

		$update = [];

		foreach ( $raw as $key => $data ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$rejections->add( Payload::UPDATE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! is_array( $data ) ) {
				$rejections->add( Payload::UPDATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			$data['ticket_id']    = $ticket_id;
			$update[ $ticket_id ] = $data;
		}

		return $update;
	}

	/**
	 * Parses the `create` part.
	 *
	 * A new ticket has no ID; one inside the data would turn the create into an unchecked update,
	 * so it is removed.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections Where rejected entries are recorded.
	 *
	 * @return array<int,array<string,mixed>> Position => data.
	 */
	private function parse_create( $raw, Rejections $rejections ): array {
		if ( ! $this->part_is_array( Payload::CREATE, $raw, $rejections ) ) {
			return [];
		}

		$create = [];

		foreach ( $raw as $key => $data ) {
			$position = $this->to_non_negative_int( $key );

			if ( null === $position ) {
				$rejections->add( Payload::CREATE, $key, __( 'The position of a new ticket must be a non-negative integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! is_array( $data ) ) {
				$rejections->add( Payload::CREATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			unset( $data['ticket_id'] );
			$create[ $position ] = $data;
		}

		return $create;
	}

	/**
	 * Parses the `delete` part.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections Where rejected entries are recorded.
	 *
	 * @return int[] The ticket IDs to delete, without duplicates.
	 */
	private function parse_delete( $raw, Rejections $rejections ): array {
		if ( ! $this->part_is_array( Payload::DELETE, $raw, $rejections ) ) {
			return [];
		}

		$delete = [];

		foreach ( $raw as $value ) {
			$ticket_id = $this->to_positive_int( $value );

			if ( null === $ticket_id ) {
				$rejections->add( Payload::DELETE, $value, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! in_array( $ticket_id, $delete, true ) ) {
				$delete[] = $ticket_id;
			}
		}

		return $delete;
	}

	/**
	 * Parses the `move` part.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections Where rejected entries are recorded.
	 *
	 * @return array<int,int> Ticket ID => destination post ID.
	 */
	private function parse_move( $raw, Rejections $rejections ): array {
		if ( ! $this->part_is_array( Payload::MOVE, $raw, $rejections ) ) {
			return [];
		}

		$move = [];

		foreach ( $raw as $key => $value ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$rejections->add( Payload::MOVE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			$destination_id = $this->to_positive_int( $value );

			if ( null === $destination_id ) {
				$rejections->add( Payload::MOVE, $key, __( 'The destination post ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			$move[ $ticket_id ] = $destination_id;
		}

		return $move;
	}

	/**
	 * Checks that a part is an array, recording a part-level rejection when it is not.
	 *
	 * @since TBD
	 *
	 * @param string     $part       The part being parsed.
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections Where the rejection is recorded.
	 *
	 * @return bool Whether the part can be parsed.
	 */
	private function part_is_array( string $part, $raw, Rejections $rejections ): bool {
		if ( is_array( $raw ) ) {
			return true;
		}

		$rejections->add(
			$part,
			null,
			sprintf(
				/* translators: %s: the part name, one of update, create, delete or move. */
				__( 'The "%s" part of the ticket changes must be an array.', 'event-tickets' ),
				$part
			)
		);

		return false;
	}

	/**
	 * Normalizes a positive integer, accepting the digit strings form fields deliver.
	 *
	 * @since TBD
	 *
	 * @param mixed $value The value to normalize.
	 *
	 * @return int|null The integer, or `null` when the value is not a positive integer.
	 */
	private function to_positive_int( $value ): ?int {
		$int = $this->to_non_negative_int( $value );

		return null !== $int && $int > 0 ? $int : null;
	}

	/**
	 * Normalizes a non-negative integer, accepting the digit strings form fields deliver.
	 *
	 * @since TBD
	 *
	 * @param mixed $value The value to normalize.
	 *
	 * @return int|null The integer, or `null` when the value is not a non-negative integer.
	 */
	private function to_non_negative_int( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value >= 0 ? $value : null;
		}

		if ( is_string( $value ) && '' !== $value && ctype_digit( $value ) ) {
			return (int) $value;
		}

		return null;
	}
}

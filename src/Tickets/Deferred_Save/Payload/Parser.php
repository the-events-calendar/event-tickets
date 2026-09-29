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
 * not is dropped and recorded in the outcome's rejections. A value that is not an array, or that
 * carries an unknown part, cannot be a payload at all and throws instead.
 *
 * The parser makes no WordPress calls beyond translation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
final class Parser {
	/**
	 * The part holding ticket ID => data pairs for existing tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const UPDATE = 'update';

	/**
	 * The part holding position => data pairs for new tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const CREATE = 'create';

	/**
	 * The part holding the list of ticket IDs to delete.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DELETE = 'delete';

	/**
	 * The part holding ticket ID => destination post ID pairs.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MOVE = 'move';

	/**
	 * Every part the contract knows.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const PARTS = [ self::UPDATE, self::CREATE, self::DELETE, self::MOVE ];

	/**
	 * Parses the raw `tec_tickets` value of a request.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw value. `null` or an empty array means "no ticket changes".
	 *
	 * @return Outcome The accepted entries and the rejected ones.
	 *
	 * @throws Malformed_Exception When the value is not an array or carries an unknown part.
	 */
	public function parse( $raw ): Outcome {
		if ( null === $raw || [] === $raw ) {
			return new Outcome( new Payload(), new Rejections() );
		}

		if ( ! is_array( $raw ) ) {
			throw new Malformed_Exception( __( 'The ticket changes must be an array.', 'event-tickets' ) );
		}

		$unknown = array_diff( array_keys( $raw ), self::PARTS );

		if ( [] !== $unknown ) {
			throw new Malformed_Exception(
				sprintf(
					/* translators: %s: the unknown key. */
					__( 'Unknown ticket changes part "%s".', 'event-tickets' ),
					reset( $unknown )
				)
			);
		}

		$rejections = new Rejections();

		[ $update, $rejections ] = $this->parse_update( $this->part( $raw, self::UPDATE ), $rejections );
		[ $create, $rejections ] = $this->parse_create( $this->part( $raw, self::CREATE ), $rejections );
		[ $delete, $rejections ] = $this->parse_delete( $this->part( $raw, self::DELETE ), $rejections );
		[ $move, $rejections ]   = $this->parse_move( $this->part( $raw, self::MOVE ), $rejections );

		// A ticket may be updated, moved or deleted, not more than one; which is meant cannot be told, so every entry goes.
		$conflicts = array_unique(
			array_merge(
				array_intersect( array_keys( $update ), $delete ),
				array_intersect( array_keys( $move ), $delete ),
				array_intersect( array_keys( $update ), array_keys( $move ) )
			)
		);

		foreach ( $conflicts as $ticket_id ) {
			$part = array_key_exists( $ticket_id, $update ) ? self::UPDATE : self::MOVE;
			unset( $update[ $ticket_id ], $move[ $ticket_id ] );
			$delete     = array_values( array_diff( $delete, [ $ticket_id ] ) );
			$rejections = $rejections->with( $part, $ticket_id, __( 'The same ticket can only be updated, moved or deleted, not more than one of these at once.', 'event-tickets' ) );
		}

		return new Outcome( new Payload( $update, $create, $delete, $move ), $rejections );
	}

	/**
	 * Returns a part as sent, or an empty array when the key is absent.
	 *
	 * Only an absent key means "no entries"; a key present with `null` is a part that is not an
	 * array and is rejected as such by the part parser.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $raw  The raw payload.
	 * @param string              $part The part name.
	 *
	 * @return mixed The raw part.
	 */
	private function part( array $raw, string $part ) {
		return array_key_exists( $part, $raw ) ? $raw[ $part ] : [];
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
	 * @param Rejections $rejections The rejections so far.
	 *
	 * @return array{0: array<int,array<string,mixed>>, 1: Rejections} Ticket ID => data, and the rejections after this part.
	 */
	private function parse_update( $raw, Rejections $rejections ): array {
		if ( ! is_array( $raw ) ) {
			return [ [], $this->reject_part( self::UPDATE, $rejections ) ];
		}

		$update = [];

		foreach ( $raw as $key => $data ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$rejections = $rejections->with( self::UPDATE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( array_key_exists( $ticket_id, $update ) ) {
				$rejections = $rejections->with( self::UPDATE, $key, $this->duplicate_ticket_message( $ticket_id ) );
				continue;
			}

			if ( ! is_array( $data ) ) {
				$rejections = $rejections->with( self::UPDATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			$data['ticket_id']    = $ticket_id;
			$update[ $ticket_id ] = $data;
		}

		return [ $update, $rejections ];
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
	 * @param Rejections $rejections The rejections so far.
	 *
	 * @return array{0: array<int,array<string,mixed>>, 1: Rejections} Position => data, and the rejections after this part.
	 */
	private function parse_create( $raw, Rejections $rejections ): array {
		if ( ! is_array( $raw ) ) {
			return [ [], $this->reject_part( self::CREATE, $rejections ) ];
		}

		$create = [];

		foreach ( $raw as $key => $data ) {
			$position = $this->to_non_negative_int( $key );

			if ( null === $position ) {
				$rejections = $rejections->with( self::CREATE, $key, __( 'The position of a new ticket must be a non-negative integer.', 'event-tickets' ) );
				continue;
			}

			if ( array_key_exists( $position, $create ) ) {
				$rejections = $rejections->with(
					self::CREATE,
					$key,
					sprintf(
						/* translators: %d: the position of the new ticket in the list sent. */
						__( 'Position %d appears more than once.', 'event-tickets' ),
						$position
					)
				);
				continue;
			}

			if ( ! is_array( $data ) ) {
				$rejections = $rejections->with( self::CREATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			unset( $data['ticket_id'] );
			$create[ $position ] = $data;
		}

		return [ $create, $rejections ];
	}

	/**
	 * Parses the `delete` part.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections The rejections so far.
	 *
	 * @return array{0: int[], 1: Rejections} The ticket IDs to delete, without duplicates, and the rejections after this part.
	 */
	private function parse_delete( $raw, Rejections $rejections ): array {
		if ( ! is_array( $raw ) ) {
			return [ [], $this->reject_part( self::DELETE, $rejections ) ];
		}

		$delete = [];

		foreach ( $raw as $value ) {
			$ticket_id = $this->to_positive_int( $value );

			if ( null === $ticket_id ) {
				$rejections = $rejections->with(
					self::DELETE,
					is_scalar( $value ) && ! is_bool( $value ) ? $value : null,
					__( 'The ticket ID must be a positive integer.', 'event-tickets' )
				);
				continue;
			}

			if ( ! in_array( $ticket_id, $delete, true ) ) {
				$delete[] = $ticket_id;
			}
		}

		return [ $delete, $rejections ];
	}

	/**
	 * Parses the `move` part.
	 *
	 * @since TBD
	 *
	 * @param mixed      $raw        The raw part.
	 * @param Rejections $rejections The rejections so far.
	 *
	 * @return array{0: array<int,int>, 1: Rejections} Ticket ID => destination post ID, and the rejections after this part.
	 */
	private function parse_move( $raw, Rejections $rejections ): array {
		if ( ! is_array( $raw ) ) {
			return [ [], $this->reject_part( self::MOVE, $rejections ) ];
		}

		$move = [];

		foreach ( $raw as $key => $value ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$rejections = $rejections->with( self::MOVE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( array_key_exists( $ticket_id, $move ) ) {
				$rejections = $rejections->with( self::MOVE, $key, $this->duplicate_ticket_message( $ticket_id ) );
				continue;
			}

			$destination_id = $this->to_positive_int( $value );

			if ( null === $destination_id ) {
				$rejections = $rejections->with( self::MOVE, $key, __( 'The destination post ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			$move[ $ticket_id ] = $destination_id;
		}

		return [ $move, $rejections ];
	}

	/**
	 * The message for a ticket that appears more than once in a part.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return string The message.
	 */
	private function duplicate_ticket_message( int $ticket_id ): string {
		return sprintf(
			/* translators: %d: the ticket ID. */
			__( 'Ticket %d appears more than once.', 'event-tickets' ),
			$ticket_id
		);
	}

	/**
	 * Records a part-level rejection for a part that is not an array.
	 *
	 * @since TBD
	 *
	 * @param string     $part       The part being parsed.
	 * @param Rejections $rejections The rejections so far.
	 *
	 * @return Rejections The rejections with the part-level one added.
	 */
	private function reject_part( string $part, Rejections $rejections ): Rejections {
		return $rejections->with(
			$part,
			null,
			sprintf(
				/* translators: %s: the part name, one of update, create, delete or move. */
				__( 'The "%s" part of the ticket changes must be an array.', 'event-tickets' ),
				$part
			)
		);
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
	 * @return int|null The integer, or `null` when the value is not a non-negative integer PHP can hold.
	 */
	private function to_non_negative_int( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value >= 0 ? $value : null;
		}

		if ( ! is_string( $value ) || '' === $value || ! ctype_digit( $value ) ) {
			return null;
		}

		$digits = ltrim( $value, '0' );

		if ( '' === $digits ) {
			return 0;
		}

		// A string beyond the integer range casts to PHP_INT_MAX, which no longer prints back as the digits sent.
		$int = (int) $digits;

		return (string) $int === $digits ? $int : null;
	}
}

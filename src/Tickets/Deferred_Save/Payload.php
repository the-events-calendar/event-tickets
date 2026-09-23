<?php
/**
 * The ticket changes sent with a post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

/**
 * Class Payload.
 *
 * Reads the `tec_tickets` array both editors send with the post save, normalizes it and
 * keeps the entries that match the contract:
 *
 *     tec_tickets = [
 *         update => [ ticket ID => data ],
 *         create => [ data, data ],
 *         delete => [ ticket ID ],
 *         move   => [ ticket ID => destination post ID ],
 *     ]
 *
 * `data` is the array Event Tickets accepts for a ticket save today and is passed on untouched,
 * with one exception: `ticket_id`. The ticket save reads the ticket to write from `data['ticket_id']`,
 * so the entry's key overwrites it on `update` and it is removed from `create`. The key is the ID
 * the checks verify; a different one inside the data must never reach the save.
 * An entry that does not match the contract is dropped and recorded as an error, keyed by ticket
 * ID for `update`, `delete` and `move` and by list position for `create`. A payload that is not an
 * array, or that carries an unknown part, is rejected as a whole.
 *
 * Instances are immutable. The checks return a new instance through `with_rejected()`, so the
 * same type flows from the request to the handler that saves the accepted entries.
 *
 * The parser makes no WordPress calls beyond translation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Payload {
	/**
	 * The part holding ticket ID => data pairs for existing tickets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const UPDATE = 'update';

	/**
	 * The part holding the list of data arrays for new tickets.
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
	public const PARTS = [ self::UPDATE, self::CREATE, self::DELETE, self::MOVE ];

	/**
	 * Ticket ID => data for existing tickets.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $update = [];

	/**
	 * Position => data for new tickets. Positions are the incoming keys and are never reindexed.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $create = [];

	/**
	 * Ticket IDs to delete.
	 *
	 * @since TBD
	 *
	 * @var int[]
	 */
	private array $delete = [];

	/**
	 * Ticket ID => destination post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,int>
	 */
	private array $move = [];

	/**
	 * The entries that were rejected, in the order they were rejected.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{part: string|null, key: int|string|float|null, message: string}>
	 */
	private array $errors = [];

	/**
	 * Whether the payload as a whole was rejected.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $valid = true;

	/**
	 * Builds a payload from the raw `tec_tickets` value of a request.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw value. `null` or an empty array means "no ticket changes".
	 *
	 * @return self The parsed payload.
	 */
	public static function from_array( $raw ): self {
		$payload = new self();

		if ( null === $raw || [] === $raw ) {
			return $payload;
		}

		if ( ! is_array( $raw ) ) {
			return $payload->with_rejected( null, null, __( 'The ticket changes must be an array.', 'event-tickets' ) );
		}

		foreach ( array_keys( $raw ) as $part ) {
			if ( ! in_array( $part, self::PARTS, true ) ) {
				return $payload->with_rejected(
					null,
					(string) $part,
					sprintf(
						/* translators: %s: the unknown key. */
						__( 'Unknown ticket changes part "%s".', 'event-tickets' ),
						$part
					)
				);
			}
		}

		$payload->parse_update( $raw[ self::UPDATE ] ?? [] );
		$payload->parse_create( $raw[ self::CREATE ] ?? [] );
		$payload->parse_delete( $raw[ self::DELETE ] ?? [] );
		$payload->parse_move( $raw[ self::MOVE ] ?? [] );
		$payload->reject_updates_that_are_also_deleted();

		return $payload;
	}

	/**
	 * Returns a copy of this payload with one entry removed and an error recorded for it.
	 *
	 * A `null` part rejects the payload as a whole: every part is emptied and `is_valid()` becomes `false`.
	 *
	 * @since TBD
	 *
	 * @param string|null           $part    One of the `PARTS` constants, or `null` for the payload itself.
	 * @param int|string|float|null $key     The ticket ID (`update`, `delete`, `move`), the position (`create`),
	 *                                       or `null` for a part or payload level error.
	 * @param string                $message What was wrong, ready to show to the user.
	 *
	 * @return self The new payload. This instance is not changed.
	 */
	public function with_rejected( ?string $part, $key, string $message ): self {
		$payload = clone $this;

		$payload->errors[] = [
			'part'    => $part,
			'key'     => $key,
			'message' => $message,
		];

		if ( null === $part ) {
			$payload->valid  = false;
			$payload->update = [];
			$payload->create = [];
			$payload->delete = [];
			$payload->move   = [];

			return $payload;
		}

		if ( null === $key ) {
			return $payload;
		}

		if ( self::DELETE === $part ) {
			$payload->delete = array_values( array_diff( $payload->delete, [ $key ] ) );

			return $payload;
		}

		unset( $payload->{$part}[ $key ] );

		return $payload;
	}

	/**
	 * Whether the payload matched the contract as a whole.
	 *
	 * A valid payload can still carry rejected entries; see `get_errors()`.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the payload is valid.
	 */
	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * Whether any accepted entry is left to act on.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the payload has ticket changes.
	 */
	public function has_changes(): bool {
		return [] !== $this->update || [] !== $this->create || [] !== $this->delete || [] !== $this->move;
	}

	/**
	 * Returns the ticket ID => data pairs for existing tickets.
	 *
	 * @since TBD
	 *
	 * @return array<int,array<string,mixed>> The accepted `update` entries.
	 */
	public function get_update(): array {
		return $this->update;
	}

	/**
	 * Returns the position => data pairs for new tickets.
	 *
	 * @since TBD
	 *
	 * @return array<int,array<string,mixed>> The accepted `create` entries, keyed by their incoming position.
	 */
	public function get_create(): array {
		return $this->create;
	}

	/**
	 * Returns the ticket IDs to delete.
	 *
	 * @since TBD
	 *
	 * @return int[] The accepted `delete` entries.
	 */
	public function get_delete(): array {
		return $this->delete;
	}

	/**
	 * Returns the ticket ID => destination post ID pairs.
	 *
	 * @since TBD
	 *
	 * @return array<int,int> The accepted `move` entries.
	 */
	public function get_move(): array {
		return $this->move;
	}

	/**
	 * Returns the rejected entries.
	 *
	 * @since TBD
	 *
	 * @return array<int,array{part: string|null, key: int|string|float|null, message: string}> The errors, in order.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Parses the `update` part.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw part.
	 *
	 * @return void
	 */
	private function parse_update( $raw ): void {
		if ( ! $this->part_is_array( self::UPDATE, $raw ) ) {
			return;
		}

		foreach ( $raw as $key => $data ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$this->reject( self::UPDATE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! is_array( $data ) ) {
				$this->reject( self::UPDATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			// The key is the checked ticket ID; the save reads the ID from the data, so the key must win.
			$data['ticket_id'] = $ticket_id;

			$this->update[ $ticket_id ] = $data;
		}
	}

	/**
	 * Parses the `create` part.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw part.
	 *
	 * @return void
	 */
	private function parse_create( $raw ): void {
		if ( ! $this->part_is_array( self::CREATE, $raw ) ) {
			return;
		}

		foreach ( $raw as $key => $data ) {
			$position = $this->to_non_negative_int( $key );

			if ( null === $position ) {
				$this->reject( self::CREATE, $key, __( 'The position of a new ticket must be a non-negative integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! is_array( $data ) ) {
				$this->reject( self::CREATE, $key, __( 'The ticket data must be an array.', 'event-tickets' ) );
				continue;
			}

			// A new ticket has no ID; one inside the data would turn the create into an unchecked update.
			unset( $data['ticket_id'] );

			$this->create[ $position ] = $data;
		}
	}

	/**
	 * Parses the `delete` part.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw part.
	 *
	 * @return void
	 */
	private function parse_delete( $raw ): void {
		if ( ! $this->part_is_array( self::DELETE, $raw ) ) {
			return;
		}

		foreach ( $raw as $value ) {
			$ticket_id = $this->to_positive_int( $value );

			if ( null === $ticket_id ) {
				$this->reject( self::DELETE, $value, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			if ( ! in_array( $ticket_id, $this->delete, true ) ) {
				$this->delete[] = $ticket_id;
			}
		}
	}

	/**
	 * Parses the `move` part.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw part.
	 *
	 * @return void
	 */
	private function parse_move( $raw ): void {
		if ( ! $this->part_is_array( self::MOVE, $raw ) ) {
			return;
		}

		foreach ( $raw as $key => $value ) {
			$ticket_id = $this->to_positive_int( $key );

			if ( null === $ticket_id ) {
				$this->reject( self::MOVE, $key, __( 'The ticket ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			$destination_id = $this->to_positive_int( $value );

			if ( null === $destination_id ) {
				$this->reject( self::MOVE, $key, __( 'The destination post ID must be a positive integer.', 'event-tickets' ) );
				continue;
			}

			$this->move[ $ticket_id ] = $destination_id;
		}
	}

	/**
	 * Drops every ticket that is both updated and deleted, since the two cannot both be meant.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function reject_updates_that_are_also_deleted(): void {
		foreach ( array_intersect( array_keys( $this->update ), $this->delete ) as $ticket_id ) {
			unset( $this->update[ $ticket_id ] );
			$this->delete = array_values( array_diff( $this->delete, [ $ticket_id ] ) );
			$this->reject( self::UPDATE, $ticket_id, __( 'The same ticket cannot be both updated and deleted.', 'event-tickets' ) );
		}
	}

	/**
	 * Checks that a part is an array, recording a part-level error when it is not.
	 *
	 * @since TBD
	 *
	 * @param string $part The part being parsed.
	 * @param mixed  $raw  The raw part.
	 *
	 * @return bool Whether the part can be parsed.
	 */
	private function part_is_array( string $part, $raw ): bool {
		if ( is_array( $raw ) ) {
			return true;
		}

		$this->reject(
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
	 * Records an error on this instance while parsing.
	 *
	 * @since TBD
	 *
	 * @param string                $part    The part.
	 * @param int|string|float|null $key     The entry key, or `null` for a part-level error.
	 * @param string                $message What was wrong.
	 *
	 * @return void
	 */
	private function reject( string $part, $key, string $message ): void {
		$this->errors[] = [
			'part'    => $part,
			'key'     => $key,
			'message' => $message,
		];
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

<?php
/**
 * The outcome of committing a payload.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

/**
 * Class Result.
 *
 * Holds the IDs of the tickets a commit created, keyed by the position their `create` entry had in
 * the payload, and one error per entry that did not go through, in the shape `Payload` records them:
 * `[ 'part' => 'update', 'key' => 123, 'message' => '...' ]`.
 *
 * Instances are immutable; every mutator returns a copy.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Result {
	/**
	 * Position in the `create` part => the ID of the ticket created from it.
	 *
	 * @since TBD
	 *
	 * @var array<int,int>
	 */
	private array $created;

	/**
	 * The entries that were rejected or failed, in order.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{part: string|null, key: int|string|float|null, message: string}>
	 */
	private array $errors;

	/**
	 * Result constructor.
	 *
	 * @since TBD
	 *
	 * @param array<int,int>                                                                   $created Position => ticket ID.
	 * @param array<int,array{part: string|null, key: int|string|float|null, message: string}> $errors  The errors so far.
	 */
	public function __construct( array $created = [], array $errors = [] ) {
		$this->created = $created;
		$this->errors  = $errors;
	}

	/**
	 * Returns a copy with one more created ticket.
	 *
	 * @since TBD
	 *
	 * @param int $position  The position of the `create` entry in the payload.
	 * @param int $ticket_id The ID of the ticket created from it.
	 *
	 * @return self The new result.
	 */
	public function with_created( int $position, int $ticket_id ): self {
		$created              = $this->created;
		$created[ $position ] = $ticket_id;
		ksort( $created );

		return new self( $created, $this->errors );
	}

	/**
	 * Returns a copy with one more error.
	 *
	 * @since TBD
	 *
	 * @param string|null           $part    One of the `Payload::PARTS`, or `null` for the payload as a whole.
	 * @param int|string|float|null $key     The ticket ID or `create` position, or `null` for a part-level error.
	 * @param string                $message What went wrong, ready to show to the user.
	 *
	 * @return self The new result.
	 */
	public function with_error( ?string $part, $key, string $message ): self {
		$errors   = $this->errors;
		$errors[] = [
			'part'    => $part,
			'key'     => $key,
			'message' => $message,
		];

		return new self( $this->created, $errors );
	}

	/**
	 * Returns a copy holding this result and another one.
	 *
	 * @since TBD
	 *
	 * @param Result $other The result to fold in.
	 *
	 * @return self The merged result.
	 */
	public function merge( Result $other ): self {
		$created = $this->created + $other->created;
		ksort( $created );

		return new self( $created, array_merge( $this->errors, $other->errors ) );
	}

	/**
	 * Returns the created ticket IDs, keyed by the position of their `create` entry.
	 *
	 * @since TBD
	 *
	 * @return array<int,int> Position => ticket ID, in position order.
	 */
	public function get_created(): array {
		return $this->created;
	}

	/**
	 * Returns the errors, in order.
	 *
	 * @since TBD
	 *
	 * @return array<int,array{part: string|null, key: int|string|float|null, message: string}> The errors.
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Returns the result as the array the editors receive.
	 *
	 * @since TBD
	 *
	 * @return array{created: array<int,int>, errors: array<int,array{part: string|null, key: int|string|float|null, message: string}>} The result.
	 */
	public function to_array(): array {
		return [
			'created' => $this->created,
			'errors'  => $this->errors,
		];
	}
}

<?php
/**
 * The entries of a payload that were refused, and why.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */

namespace TEC\Tickets\Deferred_Save\Payload;

/**
 * Class Rejections.
 *
 * An immutable, ordered list of what the parser or the checks refused: the part, the entry key
 * (a ticket ID, a `create` position, or `null` for a part or payload level rejection) and a
 * message ready to show to the user. `Result` reports them back to the editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
final class Rejections {
	/**
	 * The rejections, in order.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{part: string|null, key: int|string|float|null, message: string}>
	 */
	private array $rejections;

	/**
	 * Rejections constructor.
	 *
	 * @since TBD
	 *
	 * @param array<int,array{part: string|null, key: int|string|float|null, message: string}> $rejections The rejections, in order.
	 */
	public function __construct( array $rejections = [] ) {
		$this->rejections = $rejections;
	}

	/**
	 * Returns a copy with one more rejection at the end.
	 *
	 * @since TBD
	 *
	 * @param string|null           $part    One of the `Parser` part constants, or `null` for the payload as a whole.
	 * @param int|string|float|null $key     The entry key, or `null` for a part or payload level rejection.
	 * @param string                $message What was wrong, ready to show to the user.
	 *
	 * @return self The new list. This instance is not changed.
	 */
	public function with( ?string $part, $key, string $message ): self {
		$rejections   = $this->rejections;
		$rejections[] = [
			'part'    => $part,
			'key'     => $key,
			'message' => $message,
		];

		return new self( $rejections );
	}

	/**
	 * Returns a copy with another list's rejections after this one's.
	 *
	 * @since TBD
	 *
	 * @param Rejections $other The rejections to append.
	 *
	 * @return self The new list. Neither instance is changed.
	 */
	public function merge( Rejections $other ): self {
		return new self( array_merge( $this->rejections, $other->rejections ) );
	}

	/**
	 * Returns every rejection, in order.
	 *
	 * @since TBD
	 *
	 * @return array<int,array{part: string|null, key: int|string|float|null, message: string}> The rejections.
	 */
	public function all(): array {
		return $this->rejections;
	}

	/**
	 * Whether nothing was rejected.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the list is empty.
	 */
	public function is_empty(): bool {
		return [] === $this->rejections;
	}
}

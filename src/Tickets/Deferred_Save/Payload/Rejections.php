<?php
/**
 * The entries of a payload that were rejected, and why.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */

namespace TEC\Tickets\Deferred_Save\Payload;

/**
 * Class Rejections.
 *
 * Collects, in order, every entry the parser or the checks refused: the part, the entry key
 * (a ticket ID, a `create` position, or `null` for a part or payload level rejection) and a
 * message ready to show to the user. `Result` reports them back to the editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
class Rejections {
	/**
	 * The rejections, in the order they were added.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{part: string|null, key: int|string|float|null, message: string}>
	 */
	private array $rejections = [];

	/**
	 * Records a rejection.
	 *
	 * @since TBD
	 *
	 * @param string|null           $part    One of the `Parser` part constants, or `null` for the payload as a whole.
	 * @param int|string|float|null $key     The entry key, or `null` for a part or payload level rejection.
	 * @param string                $message What was wrong, ready to show to the user.
	 *
	 * @return void
	 */
	public function add( ?string $part, $key, string $message ): void {
		$this->rejections[] = [
			'part'    => $part,
			'key'     => $key,
			'message' => $message,
		];
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
	 * @return bool Whether the collection is empty.
	 */
	public function is_empty(): bool {
		return [] === $this->rejections;
	}
}

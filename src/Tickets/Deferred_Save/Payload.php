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
 * The accepted entries of the `tec_tickets` array both editors send with the post save:
 *
 *     update => [ ticket ID => data ],
 *     create => [ position => data ],
 *     delete => [ ticket ID ],
 *     move   => [ ticket ID => destination post ID ],
 *
 * Instances are immutable and hold what `Payload\Parser` accepted; what was rejected, and why,
 * lives in `Payload\Rejections`. The part names are `Parser`'s constants.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Payload {
	/**
	 * Ticket ID => data for existing tickets.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $update;

	/**
	 * Position => data for new tickets. Positions are the incoming keys and are never reindexed.
	 *
	 * @since TBD
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private array $create;

	/**
	 * Ticket IDs to delete.
	 *
	 * @since TBD
	 *
	 * @var int[]
	 */
	private array $delete;

	/**
	 * Ticket ID => destination post ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,int>
	 */
	private array $move;

	/**
	 * Payload constructor.
	 *
	 * The parts are trusted as given; `Payload\Parser` is what normalizes raw input into them.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $update Ticket ID => data for existing tickets.
	 * @param array<int,array<string,mixed>> $create Position => data for new tickets.
	 * @param int[]                          $delete Ticket IDs to delete.
	 * @param array<int,int>                 $move   Ticket ID => destination post ID.
	 */
	public function __construct( array $update = [], array $create = [], array $delete = [], array $move = [] ) {
		$this->update = $update;
		$this->create = $create;
		$this->delete = $delete;
		$this->move   = $move;
	}

	/**
	 * Whether any entry is left to act on.
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
	 * @return array<int,array<string,mixed>> The `update` entries.
	 */
	public function get_update(): array {
		return $this->update;
	}

	/**
	 * Returns the position => data pairs for new tickets.
	 *
	 * @since TBD
	 *
	 * @return array<int,array<string,mixed>> The `create` entries, keyed by their incoming position.
	 */
	public function get_create(): array {
		return $this->create;
	}

	/**
	 * Returns the ticket IDs to delete.
	 *
	 * @since TBD
	 *
	 * @return int[] The `delete` entries.
	 */
	public function get_delete(): array {
		return $this->delete;
	}

	/**
	 * Returns the ticket ID => destination post ID pairs.
	 *
	 * @since TBD
	 *
	 * @return array<int,int> The `move` entries.
	 */
	public function get_move(): array {
		return $this->move;
	}
}

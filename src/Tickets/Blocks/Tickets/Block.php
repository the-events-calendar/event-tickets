<?php
/**
 * Provides the information required to register the Tickets block server-side.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */

namespace TEC\Tickets\Blocks\Tickets;

/**
 * Class Block.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */
class Block {

	/**
	 * Returns the name the block is registered with in the `registerBlockType` function.
	 *
	 * @since TBD
	 *
	 * @return string The name the block is registered with in the `registerBlockType` function.
	 */
	public static function getName(): string {
		return 'tribe/tickets';
	}

	/**
	 * Returns the arguments required to register the block.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The arguments required to register the Tickets block.
	 */
	public function get_block_registration_args(): array {
		return [
			'title'       => _x( 'Tickets', 'Block title', 'event-tickets' ),
			'description' => _x( 'Sell tickets and register attendees.', 'Block description', 'event-tickets' ),
		];
	}
}
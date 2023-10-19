<?php
/**
 * Provides the information required to register the Tickets block server-side.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */

namespace TEC\Tickets\Blocks\Tickets;

use TEC\Common\Blocks\Block_Interface;

/**
 * Class Block.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */
class Block implements Block_Interface {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public static function getName(): string {
		// Using this name for back-compatibility reasons.
		return 'tribe/tickets';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function get_block_registration_args(): array {
		return [
			'title'       => _x( 'Tickets', 'Block title', 'event-tickets' ),
			'description' => _x( 'Sell tickets and register attendees.', 'Block description', 'event-tickets' ),
		];
	}
}
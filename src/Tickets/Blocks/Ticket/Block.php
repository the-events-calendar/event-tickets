<?php
/**
 * Provides the information required to register the Ticket (singular) block server-side.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */

namespace TEC\Tickets\Blocks\Ticket;

use TEC\Common\Blocks\Block_Interface;

/**
 * Class Block.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Ticket;
 */
class Block implements Block_Interface {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public static function getName(): string {
		// Using this name for back-compatibility reasons.
		return 'tribe/tickets-item';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function get_block_registration_args(): array {
		return [
			'title'       => _x( 'Event Ticket', '', 'event-tickets' ),
			'description' => _x( 'A single configured ticket type.', '', 'event-tickets' ),
		];
	}
}
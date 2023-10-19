<?php
/**
 * Provides the information required to register the Ticket (singular) block server-side.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */

namespace TEC\Tickets\Blocks\Ticket;

use Tribe__Editor__Blocks__Abstract as Abstract_Block;

/**
 * Class Block.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\Ticket;
 */
class Block extends Abstract_Block {
	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9.2
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets-item';
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9.2
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function render( $attributes = [] ) {
		// This block has no render.
		return '';
	}
}
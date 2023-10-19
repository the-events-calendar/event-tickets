<?php
/**
 * Handles the registration of all the Blocks managed by the plugin.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks;
 */

namespace TEC\Tickets\Blocks;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks;
 */
class Controller extends \TEC\Common\Contracts\Provider\Controller {
	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Tickets\Block::class );
		$this->container->singleton( Ticket\Block::class );

		$tickets_block = $this->container->get( Tickets\Block::class );
		register_block_type(
			__DIR__ . '/Tickets/block.json',
			$tickets_block->get_block_registration_args()
		);

		$ticket_block = $this->container->get( Ticket\Block::class );
		register_block_type(
			__DIR__ . '/Ticket/block.json',
			$ticket_block->get_block_registration_args()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		unregister_block_type( Tickets\Block::getName() );
		unregister_block_type( Ticket\Block::getName() );
	}
}

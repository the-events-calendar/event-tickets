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
use Tribe__Tickets__Main as Tickets_Main;

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

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	protected function get_registration_block_type() {
		return __DIR__ . '/block.json';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	protected function get_registration_args( array $args ): array {
		$args['title']       = _x( 'Event Ticket', 'Block title', 'event-tickets' );
		$args['description'] = _x( 'A single configured ticket type.', 'Block description', 'event-tickets' );

		return $args;
	}

	public function assets() {
		add_action( 'admin_enqueue_scripts', function () {
			$plugin = Tickets_Main::instance();

			wp_register_script(
				'tec-tickets-ticket-item-block-editor-script',
				$plugin->plugin_url . 'build/Tickets/Blocks/Ticket/editor.js',
				[ 'tribe-common-gutenberg-vendor', 'tribe-tickets-gutenberg-vendor' ]
			);

			wp_register_style(
				'tec-tickets-ticket-item-block-editor-style',
				$plugin->plugin_url . 'build/Tickets/Blocks/Ticket/editor.css'
			);
		} );
	}
}
<?php
/**
 * The scripts of Recurring Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Recurring_Tickets\Editor\Classic;
use Tribe__Events__Main as TEC;

/**
 * Registers the editor scripts, each loaded only when its editor is editing an event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Assets {
	/**
	 * Registers the scripts.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		$plugin = tribe( 'tickets.main' );

		tec_asset(
			$plugin,
			'tec-tickets-recurring-tickets-block-editor',
			$plugin->plugin_url . 'build/RecurringTickets/block-editor.js',
			[ 'wp-hooks' ],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'conditionals' => [ $this, 'is_editing_an_event_in_the_block_editor' ],
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-recurring-tickets-classic-editor',
			$plugin->plugin_url . 'build/RecurringTickets/classic-editor.js',
			// After the tickets panel script, whose recurrence events it follows.
			[ 'jquery', 'event-tickets-admin-js' ],
			'admin_enqueue_scripts',
			[
				'in_footer'    => true,
				'conditionals' => [ $this, 'is_editing_an_event_in_the_classic_editor' ],
			]
		);
	}

	/**
	 * Whether the current screen edits, in the block editor, an event recurring event tickets are offered on.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the block editor is editing an event.
	 */
	public function is_editing_an_event_in_the_block_editor(): bool {
		return tribe_context()->is_editing_post( TEC::POSTTYPE )
			&& tribe( 'editor' )->should_load_blocks()
			// A seated event keeps the block's own rule for recurring events.
			&& tribe( Classic::class )->offers_recurring_tickets( (int) get_the_ID() );
	}

	/**
	 * Whether the current screen edits an event in the classic editor.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the classic editor is editing an event.
	 */
	public function is_editing_an_event_in_the_classic_editor(): bool {
		return tribe_context()->is_editing_post( TEC::POSTTYPE ) && ! tribe( 'editor' )->should_load_blocks();
	}
}
